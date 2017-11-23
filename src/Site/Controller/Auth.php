<?php

namespace Site\Controller;

use Api\Library\Shared\Communicate\Communicate;
use Api\Library\Shared\Palaso\Exception\UserUnauthorizedException;
use Api\Library\Shared\Website;
use Api\Model\Shared\Command\UserCommands;
use Api\Model\Shared\UserModel;
use Silex\Application;
use Site\OAuth\OAuthBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Security;

defined('ENVIRONMENT') or exit('No direct script access allowed');

class Auth extends App
{
    // return status
    const LOGIN_FAIL = 'loginFail';
    const LOGIN_FAIL_USER_UNAUTHORIZED = 'loginFailUserUnauthorized';
    const LOGIN_SUCCESS = 'loginSuccess';

    public function view(Request $request, Application $app, $appName, $resetPasswordKey = '')
    {
        switch ($appName) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'reset_password':
                $user = new UserModel();
                if (!$user->readByProperty('resetPasswordKey', $resetPasswordKey)) {
                    $app['session']->getFlashBag()->add('errorMessage', 'Your password reset cannot be completed. Please try again.');

                    return $app->redirect($this->isLoggedIn($app) ? '/auth/logout' : '/auth/login');
                }

                if (!$user->hasForgottenPassword(false)) {
                    $app['session']->getFlashBag()->add('errorMessage', 'Your password reset cannot be completed. It may have expired. Please try again.');

                    return $app->redirect($this->isLoggedIn($app) ? '/auth/logout' : '/auth/login');
                }

                // no break; - intentional fall through to next case
            case 'forgot_password':
            case 'login':
            case 'link_oauth_account':
                if($this->isLoggedIn($app)) {
                    return $app->redirect('/app/projects');
                }
                $this->setupAngularAppVariables($app, $appName);
                $this->setupAuthView($request, $app);

                return $this->renderPage($app, 'angular-app');
                break;
            default:
                return $this->renderPage($app, $appName);
        }
    }

    public function forgotPassword(Request $request, Application $app)
    {
        $usernameOrEmail = UserCommands::sanitizeInput($request->request->get('_username'));
        $user = new UserModel();
        if (!$user->readByUsernameOrEmail($usernameOrEmail)) {
            $app['session']->getFlashBag()->add('errorMessage', 'User not found.');
            return $this->view($request, $app, 'forgot_password');
        } else if (!$user->active) {
            $app['session']->getFlashBag()->add('errorMessage', 'Access denied.');
            return $this->view($request, $app, 'forgot_password');
        }

        if (!$user->hasRoleOnSite($this->website)) {
            $user->siteRole[$this->website->domain] = $this->website->userDefaultSiteRole;
        }

        Communicate::sendForgotPasswordVerification($user, $this->website);
        $app['session']->getFlashBag()->add('infoMessage', 'Password Reset email sent for username "'.$usernameOrEmail.'"');
        return $app->redirect('/auth/login');
    }

    /**
     * @param Request $request
     * @param Application $app
     */
    private function setupAuthView(Request $request, Application $app)
    {
        $this->data['last_username'] = $app['session']->get(Security::LAST_USERNAME);
        if ($app['session']->has(OAuthBase::SESSION_KEY_OAUTH_TOKEN_ID_TO_LINK)) {
            $this->data['oauth_id_for_login'] = $app['session']->get(OAuthBase::SESSION_KEY_OAUTH_TOKEN_ID_TO_LINK);
            $name = $app['session']->get(OAuthBase::SESSION_KEY_OAUTH_FULL_NAME);
            $this->data['oauth_full_name_for_login'] = $name;
            $email = $app['session']->get(OAuthBase::SESSION_KEY_OAUTH_EMAIL_ADDRESS);
            $this->data['oauth_email_for_login'] = $email;
            $avatar = $app['session']->get(OAuthBase::SESSION_KEY_OAUTH_AVATAR_URL);
            $link = Communicate::calculateSignupUrl($this->website, $email, $name, $avatar);
            $this->data['oauth_uri_for_signup'] = $link;
        }

        $website = Website::get();
        $this->data['website_name'] = $website->name;

        $errorMsg = $app['security.last_error']($request);
        if ($errorMsg == 'Bad credentials.') {
            $user = new UserModel();
            if ($user->readByUsernameOrEmail($this->data['last_username']) &&
                !$user->active) {
                $errorMsg = 'Your account has been deactivated';
            } else {
                $errorMsg = 'Invalid username or password.';
            }
        }

        if ($errorMsg) {
            $app['session']->getFlashBag()->add('errorMessage', $errorMsg);
            if ($app['session']->has(Security::AUTHENTICATION_ERROR)) {
                $app['session']->remove(Security::AUTHENTICATION_ERROR);
            }
        }
    }

    /**
     * @param Application $app
     * @param string $resetPasswordKey
     * @param string $newPassword
     * @throws UserUnauthorizedException
     * @return string $userId
     */
    public static function resetPassword(Application $app, $resetPasswordKey = '', $newPassword = '')
    {
        $user = new UserModel();

        if (!$user->readByProperty('resetPasswordKey', $resetPasswordKey)) {
            $app['session']->getFlashBag()->add('errorMessage', 'Your password reset cannot be completed. Please try again.');
            return false;
        }

        if (!$user->hasForgottenPassword()) {
            $app['session']->getFlashBag()->add('errorMessage', 'Your password reset cannot be completed. It may have expired. Please try again.');
            return false;
        }

        $userId = $user->id->asString();
        UserCommands::changePassword($userId, $newPassword, $userId);
        $app['session']->getFlashBag()->add('infoMessage', 'Your password has been reset. Please login.');
        return $user->write();
    }

    /**
     * Taken from http://stackoverflow.com/questions/5886713/automatic-post-registration-user-authentication
     * @param Application $app
     * @param string $username
     * @param string $password
     */
    public static function login(Application $app, $username, $password)
    {
        $subRequest = Request::create(
            '/app/login_check', 'POST',
            array('_username' => $username, '_password' => $password),
            $app['request']->cookies->all(), array(), $app['request']->server->all()
        );
        $app->handle($subRequest, HttpKernelInterface::MASTER_REQUEST, false);
    }

    /**
     * @param string $status
     * @param string $uri
     * @param string $method
     * @return array
     */
    public static function result($status, $uri, $method = 'location')
    {
        return array(
            'status' => $status,
            'redirect' => array(
                'url' => $uri,
                'method' => $method
            )
        );
    }
}
