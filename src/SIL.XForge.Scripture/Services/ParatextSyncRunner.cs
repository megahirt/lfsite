using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using System.Xml.Linq;
using Hangfire;
using Hangfire.Server;
using Microsoft.Extensions.Logging;
using Microsoft.Extensions.Options;
using ShareDB;
using ShareDB.RichText;
using SIL.XForge.Configuration;
using SIL.XForge.DataAccess;
using SIL.XForge.Models;
using SIL.XForge.Scripture.Models;

namespace SIL.XForge.Scripture.Services
{
    public class ParatextSyncRunner
    {
        private static readonly Dictionary<string, string> BookNames = new Dictionary<string, string>
        {
            { "GEN", "Genesis" },
            { "EXO", "Exodus" },
            { "LEV", "Leviticus" },
            { "NUM", "Numbers" },
            { "DEU", "Deuteronomy" },
            { "JOS", "Joshua" },
            { "JDG", "Judges" },
            { "RUT", "Ruth" },
            { "1SA", "1 Samuel" },
            { "2SA", "2 Samuel" },
            { "1KI", "1 Kings" },
            { "2KI", "2 Kings" },
            { "1CH", "1 Chronicles" },
            { "2CH", "2 Chronicles" },
            { "EZR", "Ezra" },
            { "NEH", "Nehemiah" },
            { "EST", "Esther" },
            { "JOB", "Job" },
            { "PSA", "Psalm" },
            { "PRO", "Proverbs" },
            { "ECC", "Ecclesiastes" },
            { "SNG", "Song of Songs" },
            { "ISA", "Isaiah" },
            { "JER", "Jeremiah" },
            { "LAM", "Lamentations" },
            { "EZK", "Ezekiel" },
            { "DAN", "Daniel" },
            { "HOS", "Hosea" },
            { "JOL", "Joel" },
            { "AMO", "Amos" },
            { "OBA", "Obadiah" },
            { "JON", "Jonah" },
            { "MIC", "Micah" },
            { "NAM", "Nahum" },
            { "HAB", "Habakkuk" },
            { "ZEP", "Zephaniah" },
            { "HAG", "Haggai" },
            { "ZEC", "Zechariah" },
            { "MAL", "Malachi" },
            { "MAT", "Matthew" },
            { "MRK", "Mark" },
            { "LUK", "Luke" },
            { "JHN", "John" },
            { "ACT", "Acts" },
            { "ROM", "Romans" },
            { "1CO", "1 Corinthians" },
            { "2CO", "2 Corinthians" },
            { "GAL", "Galatians" },
            { "EPH", "Ephesians" },
            { "PHP", "Philippians" },
            { "COL", "Colossians" },
            { "1TH", "1 Thessalonians" },
            { "2TH", "2 Thessalonians" },
            { "1TI", "1 Timothy" },
            { "2TI", "2 Timothy" },
            { "TIT", "Titus" },
            { "PHM", "Philemon" },
            { "HEB", "Hebrews" },
            { "JAS", "James" },
            { "1PE", "1 Peter" },
            { "2PE", "2 Peter" },
            { "1JN", "1 John" },
            { "2JN", "2 John" },
            { "3JN", "3 John" },
            { "JUD", "Jude" },
            { "REV", "Revelation" },
            { "TOB", "Tobit" },
            { "JDT", "Judith" },
            { "ESG", "Esther (Greek)" },
            { "WIS", "The Wisdom of Solomon" },
            { "SIR", "Sirach" },
            { "BAR", "Baruch" },
            { "LJE", "Letter of Jeremiah" },
            { "S3Y", "Song of Three Young Men" },
            { "SUS", "Susanna" },
            { "BEL", "Bel and the Dragon" },
            { "1MA", "1 Maccabees" },
            { "2MA", "2 Maccabees" },
            { "1ES", "1 Esdras" },
            { "2ES", "2 Esdras" },
            { "MAN", "The Prayer of Manasseh" }
        };

        private readonly IRepository<UserEntity> _users;
        private readonly IRepository<SyncJobEntity> _jobs;
        private readonly IRepository<SFProjectEntity> _projects;
        private readonly IParatextService _paratextService;
        private readonly IOptions<SiteOptions> _siteOptions;
        private readonly IOptions<RealtimeOptions> _realtimeOptions;
        private readonly IRepository<TextEntity> _texts;
        private readonly DeltaUsxMapper _deltaUsxMapper;
        private readonly ILogger<ParatextSyncRunner> _logger;

        private SyncJobEntity _job;
        private int _stepCount;
        private int _step;

        public ParatextSyncRunner(IOptions<SiteOptions> siteOptions,
            IOptions<RealtimeOptions> realtimeOptions, IRepository<UserEntity> users,
            IRepository<SyncJobEntity> jobs, IRepository<SFProjectEntity> projects,
            IParatextService paratextService, IRepository<TextEntity> texts,
            DeltaUsxMapper deltaUsxMapper, ILogger<ParatextSyncRunner> logger)
        {
            _siteOptions = siteOptions;
            _realtimeOptions = realtimeOptions;
            _users = users;
            _jobs = jobs;
            _projects = projects;
            _paratextService = paratextService;
            _texts = texts;
            _deltaUsxMapper = deltaUsxMapper;
            _logger = logger;
        }

        private string WorkingDir => Path.Combine(_siteOptions.Value.SiteDir, "sync");

        public async Task RunAsync(PerformContext context, IJobCancellationToken cancellationToken, string userId,
            string jobId)
        {
            _job = await _jobs.UpdateAsync(j => j.Id == jobId, u => u
                .Set(j => j.BackgroundJobId, context.BackgroundJob.Id)
                .Set(j => j.State, SyncJobEntity.SyncingState));
            if (_job == null)
                return;

            try
            {
                if ((await _users.TryGetAsync(userId)).TryResult(out UserEntity user))
                {
                    if ((await _projects.TryGetAsync(_job.ProjectRef)).TryResult(out SFProjectEntity project))
                    {
                        if (!Directory.Exists(WorkingDir))
                            Directory.CreateDirectory(WorkingDir);

                        using (var conn = new Connection(new Uri($"ws://localhost:{_realtimeOptions.Value.Port}")))
                        {
                            await conn.ConnectAsync();

                            bool hasSource = project.TranslateConfig.Enabled;
                            string sourceParatextId = project.TranslateConfig.SourceParatextId;
                            IReadOnlyList<string> sourceBooks = null;
                            if (hasSource)
                                sourceBooks = await _paratextService.GetBooksAsync(user, sourceParatextId);

                            string targetParatextId = project.ParatextId;
                            IReadOnlyList<string> targetBooks = await _paratextService.GetBooksAsync(user,
                                targetParatextId);

                            var booksToSync = new HashSet<string>(targetBooks);
                            if (hasSource)
                                booksToSync.IntersectWith(sourceBooks);

                            var booksToDelete = new HashSet<string>();
                            if (hasSource)
                                booksToDelete.UnionWith(GetBooksToDelete(project, sourceParatextId, sourceBooks));
                            booksToDelete.UnionWith(GetBooksToDelete(project, targetParatextId, targetBooks));

                            _step = 0;
                            _stepCount = booksToSync.Count * 3;
                            if (hasSource)
                                _stepCount *= 2;
                            _stepCount += booksToDelete.Count;
                            foreach (string bookId in booksToSync)
                            {
                                if (!BookNames.TryGetValue(bookId, out string name))
                                    name = bookId;
                                TextEntity text = await _texts.UpdateAsync(
                                    t => t.ProjectRef == project.Id && t.BookId == bookId,
                                    u => u.SetOnInsert(t => t.Name, name)
                                          .SetOnInsert(t => t.BookId, bookId)
                                          .SetOnInsert(t => t.ProjectRef, project.Id)
                                          .SetOnInsert(t => t.OwnerRef, userId), upsert: true);

                                if (hasSource)
                                {
                                    await SyncOrCloneBookAsync(user, conn, project, text, TextType.Source,
                                        sourceParatextId);
                                }
                                await SyncOrCloneBookAsync(user, conn, project, text, TextType.Target,
                                    targetParatextId);
                            }

                            foreach (string bookId in booksToDelete)
                            {
                                TextEntity text = await _texts.DeleteAsync(
                                    t => t.ProjectRef == project.Id && t.BookId == bookId);

                                if (hasSource)
                                    await DeleteBookAsync(conn, project, text, TextType.Source, sourceParatextId);
                                await DeleteBookAsync(conn, project, text, TextType.Target, targetParatextId);
                                await UpdateProgress();
                            }

                            await conn.CloseAsync();
                        }

                        _job = await _jobs.UpdateAsync(_job, u => u
                            .Set(j => j.State, SyncJobEntity.IdleState)
                            .Unset(j => j.BackgroundJobId));
                        await _projects.UpdateAsync(project,
                            u => u.Set(p => p.LastSyncedDate, _job.DateModified));
                    }
                }
            }
            catch (Exception e)
            {
                _logger.LogError(e, "Error occurred while executing Paratext sync job '{Job}'", _job.Id);
                await _jobs.UpdateAsync(_job, u => u
                    .Set(j => j.State, SyncJobEntity.HoldState)
                    .Unset(j => j.BackgroundJobId));
            }
        }

        private async Task UpdateProgress()
        {
            _step++;
            double percentCompleted = (double)_step / _stepCount;
            _job = await _jobs.UpdateAsync(_job, u => u
                .Set(j => j.PercentCompleted, percentCompleted));
        }

        private IEnumerable<string> GetBooksToDelete(SFProjectEntity project, string paratextId,
            IEnumerable<string> books)
        {
            string projectPath = GetProjectPath(project, paratextId);
            var booksToDelete = new HashSet<string>(Directory.Exists(projectPath)
                ? Directory.EnumerateFiles(projectPath).Select(Path.GetFileNameWithoutExtension)
                : Enumerable.Empty<string>());
            booksToDelete.ExceptWith(books);
            return booksToDelete;
        }

        private async Task DeleteBookAsync(Connection conn, SFProjectEntity project, TextEntity text, TextType textType,
            string paratextId)
        {
            string projectPath = GetProjectPath(project, paratextId);
            File.Delete(GetBookTextFileName(projectPath, text.BookId));
            var tasks = new List<Task>();
            foreach (Chapter chapter in text.Chapters)
                tasks.Add(DeleteChapterAsync(conn, text, chapter.Number, textType));
            await Task.WhenAll(tasks);
        }

        private async Task DeleteChapterAsync(Connection conn, TextEntity text, int chapter, TextType textType)
        {
            Document<Delta> doc = GetShareDBDocument(conn, text, chapter, textType);
            await doc.FetchAsync();
            await doc.DeleteAsync();
        }

        private async Task SyncOrCloneBookAsync(UserEntity user, Connection conn, SFProjectEntity project,
            TextEntity text, TextType textType, string paratextId)
        {
            string projectPath = GetProjectPath(project, paratextId);
            if (!Directory.Exists(projectPath))
                Directory.CreateDirectory(projectPath);

            string fileName = GetBookTextFileName(projectPath, text.BookId);
            if (File.Exists(fileName))
                await SyncBookAsync(user, conn, text, textType, paratextId, fileName);
            else
                await CloneBookAsync(user, conn, text, textType, paratextId, fileName);
        }

        private async Task SyncBookAsync(UserEntity user, Connection conn, TextEntity text, TextType textType,
            string paratextId, string fileName)
        {
            var docs = new SortedList<int, Document<Delta>>();
            var tasks = new List<Task>();
            foreach (Chapter chapter in text.Chapters)
            {
                Document<Delta> doc = GetShareDBDocument(conn, text, chapter.Number, textType);
                docs[chapter.Number] = doc;
                tasks.Add(doc.FetchAsync());
            }
            await Task.WhenAll(tasks);
            XElement bookTextElem = await LoadBookTextAsync(fileName);

            XElement oldUsxElem = bookTextElem.Element("usx");
            if (oldUsxElem == null)
                throw new InvalidOperationException("Invalid USX data, missing 'usx' element.");
            XElement bookElem = oldUsxElem.Element("book");
            if (bookElem == null)
                throw new InvalidOperationException("Invalid USX data, missing 'book' element.");
            XElement newUsxElem = _deltaUsxMapper.ToUsx((string)oldUsxElem.Attribute("version"),
                (string)bookElem.Attribute("code"), (string)bookElem, docs.Values.Select(d => d.Data));

            var revision = (string)bookTextElem.Attribute("revision");

            string bookText;
            if (XNode.DeepEquals(oldUsxElem, newUsxElem))
            {
                bookText = await _paratextService.GetBookTextAsync(user, paratextId, text.BookId);
            }
            else
            {
                bookText = await _paratextService.UpdateBookTextAsync(user, paratextId, text.BookId, revision,
                    newUsxElem.ToString());
            }
            await UpdateProgress();

            bookTextElem = XElement.Parse(bookText);

            tasks.Clear();
            bool chaptersChanged = false;
            IReadOnlyDictionary<int, (Delta Delta, int LastVerse)> deltas = _deltaUsxMapper.ToChapterDeltas(paratextId,
                bookTextElem.Element("usx"));
            var chapters = new List<Chapter>();
            foreach (KeyValuePair<int, (Delta Delta, int LastVerse)> kvp in deltas)
            {
                if (docs.TryGetValue(kvp.Key, out Document<Delta> doc))
                {
                    Delta diffDelta = doc.Data.Diff(kvp.Value.Delta);
                    tasks.Add(doc.SubmitOpAsync(diffDelta));
                    docs.Remove(kvp.Key);
                }
                else
                {
                    tasks.Add(doc.CreateAsync(kvp.Value.Delta));
                    chaptersChanged = true;
                }
                chapters.Add(new Chapter { Number = kvp.Key, LastVerse = kvp.Value.LastVerse });
            }
            foreach (Document<Delta> doc in docs.Values)
            {
                tasks.Add(doc.DeleteAsync());
                chaptersChanged = true;
            }
            await Task.WhenAll(tasks);
            if (chaptersChanged)
                await _texts.UpdateAsync(text, u => u.Set(t => t.Chapters, chapters));
            await UpdateProgress();

            await SaveBookTextAsync(bookTextElem, fileName);
            await UpdateProgress();
        }

        private async Task CloneBookAsync(UserEntity user, Connection conn, TextEntity text, TextType textType,
            string paratextId, string fileName)
        {
            string bookText = await _paratextService.GetBookTextAsync(user, paratextId, text.BookId);
            await UpdateProgress();

            var bookTextElem = XElement.Parse(bookText);

            IReadOnlyDictionary<int, (Delta Delta, int LastVerse)> deltas = _deltaUsxMapper.ToChapterDeltas(paratextId,
                bookTextElem.Element("usx"));
            var tasks = new List<Task>();
            var chapters = new List<Chapter>();
            foreach (KeyValuePair<int, (Delta Delta, int LastVerse)> kvp in deltas)
            {
                Document<Delta> doc = GetShareDBDocument(conn, text, kvp.Key, textType);
                tasks.Add(doc.CreateAsync(kvp.Value.Delta));
                chapters.Add(new Chapter { Number = kvp.Key, LastVerse = kvp.Value.LastVerse });
            }
            await Task.WhenAll(tasks);
            await _texts.UpdateAsync(text, u => u.Set(t => t.Chapters, chapters));
            await UpdateProgress();

            await SaveBookTextAsync(bookTextElem, fileName);
            await UpdateProgress();
        }

        private async Task<XElement> LoadBookTextAsync(string fileName)
        {
            using (var stream = new FileStream(fileName, FileMode.Open))
            {
                return await XElement.LoadAsync(stream, LoadOptions.None, CancellationToken.None);
            }
        }

        private async Task SaveBookTextAsync(XElement bookTextElem, string fileName)
        {
            using (var stream = new FileStream(fileName, FileMode.Create))
            {
                await bookTextElem.SaveAsync(stream, SaveOptions.None, CancellationToken.None);
            }
        }

        private string GetProjectPath(SFProjectEntity project, string paratextId)
        {
            return Path.Combine(WorkingDir, project.Id, paratextId);
        }

        private string GetBookTextFileName(string projectPath, string bookId)
        {
            return Path.Combine(projectPath, bookId + ".xml");
        }

        private Document<Delta> GetShareDBDocument(Connection conn, TextEntity text, int chapter, TextType textType)
        {
            return conn.Get<Delta>("text_data", TextEntity.GetTextDataId(text.Id, chapter, textType));
        }
    }
}