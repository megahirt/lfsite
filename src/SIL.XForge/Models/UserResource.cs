using JsonApiDotNetCore.Models;

namespace SIL.XForge.Models
{
    [Resource("users")]
    public abstract class UserResource : Resource
    {
        [Attr]
        public string Username { get; set; }
        [Attr]
        public string Name { get; set; }
        [Attr]
        public string Email { get; set; }
        [Attr(isImmutable: true)]
        public string CanonicalEmail { get; set; }
        [Attr]
        public string GoogleId { get; set; }
        [Attr]
        public string Password { get; set; }
        [Attr]
        public string ParatextId { get; set; }
        [Attr]
        public string Role { get; set; }
        [Attr]
        public bool Active { get; set; }
        [Attr]
        public string AvatarUrl { get; set; }
    }
}
