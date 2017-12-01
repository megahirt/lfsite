namespace SIL.XForge.WebApi.Server.Models
{
    public class ProjectRoles : RolesBase
    {
        public const string Manager = "project_manager";
        public const string Contributor = "contributor";
        public const string None = "none";

        public bool HasRight(Project project, string userId, Right right)
        {
            if (project.Users.TryGetValue(userId, out ProjectRole user))
                return Rights[user.Role].Contains(right);
            return false;
        }
    }
}
