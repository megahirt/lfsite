using Autofac;
using Microsoft.Extensions.DependencyInjection;
using SIL.XForge.Models;
using SIL.XForge.Scripture.Models;
using SIL.XForge.Services;

namespace SIL.XForge.Scripture.Services
{
    public static class SFJsonApiServiceCollectionExtensions
    {
        public static IServiceCollection AddSFJsonApi(this IServiceCollection services, IMvcBuilder mvcBuilder,
            ContainerBuilder containerBuilder)
        {
            services.AddJsonApi(mvcBuilder, containerBuilder, mapConfig =>
                {
                    mapConfig.CreateMap<UserEntity, SFUserResource>()
                        .IncludeBase<UserEntity, UserResource>()
                        .ReverseMap();
                });

            services.AddSingleton<IParatextService, ParatextService>();
            services.AddSingleton<DeltaUsxMapper>();
            return services;
        }
    }
}
