namespace SIL.XForge.Scripture.Models
{
    public class TranslateProjectUserConfig
    {
        public bool IsTargetTextRight { get; set; } = true;
        public string SelectedTextRef { get; set; } = "";
        public string SelectedSegment { get; set; } = "";
        public int SelectedSegmentChecksum { get; set; }
    }
}
