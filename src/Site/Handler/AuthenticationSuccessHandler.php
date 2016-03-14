<?php

namespace Site\Handler;

use Api\Library\Shared\Website;
use Api\Model\ProjectModel;
use Api\Model\Shared\Rights\SiteRoles;
use Api\Model\Shared\Rights\SystemRoles;
use Api\Model\UserModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    /**
     * Constructor.
     *
     * @param HttpUtils $httpUtils
     * @param array     $options   Options for processing a successful authentication attempt.
     * @param string|null $providerKey
     */
    public function __construct(HttpUtils $httpUtils, array $options = array(), $providerKey = null) {
        parent::__construct($httpUtils, $options);
        $this->setProviderKey($providerKey);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token) {
        $username = $token->getUser()->getUsername();
        $user = new UserModel();
        $website = Website::get();

        // automatically logout if 1) the user doesn't exist or 2) the user is not a system admin and has no site rights on the current site
        if (! $user->readByUserName($username) or
            (($user->role != SystemRoles::SYSTEM_ADMIN) and
            !($user->siteRole->offsetExists($website->domain) and
                ($user->siteRole[$website->domain] != SiteRoles::NONE)))) {
            return $this->httpUtils->createRedirectResponse($request, '/app/logout');
        }


        $projectId = $user->getCurrentProjectId($website->domain);

        // redirect to page before the login screen was presented, or to the default project for this user
        $referer = $this->determineTargetUrl($request);
        if ($referer and strpos($referer, '/app/') !== false) {
            return $this->httpUtils->createRedirectResponse($request, $referer);
        } elseif ($projectId) {
            $project = ProjectModel::getById($projectId);
            $url = '/app/'.$project->appName.'/'.$projectId;

            return $this->httpUtils->createRedirectResponse($request, $url);
        } else {
            return $this->httpUtils->createRedirectResponse($request, '/');
        }
    }
}
