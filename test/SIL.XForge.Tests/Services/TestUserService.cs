using AutoMapper;
using JsonApiDotNetCore.Services;
using SIL.XForge.DataAccess;
using SIL.XForge.Models;

namespace SIL.XForge.Services
{
    public class TestUserService : UserService<TestUserResource>
    {
        public TestUserService(IJsonApiContext jsonApiContext, IMapper mapper, IUserAccessor userAccessor,
            IRepository<UserEntity> users) : base(jsonApiContext, mapper, userAccessor, users)
        {
        }
    }
}
