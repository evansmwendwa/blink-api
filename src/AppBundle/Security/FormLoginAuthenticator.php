<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FormLoginAuthenticator extends AbstractGuardAuthenticator
{
    private $encoder;
    private $router;

    public function __construct($passwordEncoder, $router)
    {
        $this->encoder = $passwordEncoder;
        $this->router = $router;
    }

    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() != '/login_check') {
            return null;
        }

        $username = $request->request->get('_username');
        $password = $request->request->get('_password');

        return array(
            'username' => $username,
            'password' => $password
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = $credentials['username'];
        return $userProvider->loadUserByUsername($username);
    }


    public function checkCredentials($credentials, UserInterface $user)
    {

        // check if user password has expired
        // (always the case for default social media accounts)

        /////if($user->getIsPasswordExpired()){
        //////    return false;
        ////}
        /**
        * If any value other than true is returned, authentication will
        * fail. You may also throw an AuthenticationException if you wish
        * to cause authentication to fail.
        */
        $plainPassword = $credentials['password'];

        $validPassword =  $this->encoder->isPasswordValid($user, $plainPassword);

        if(!$validPassword){
            throw new BadCredentialsException();
        }

        return $validPassword;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetPath = null;

        // if the user hit a secure page and start() was called, this was
        // the URL they were on, and probably where you want to redirect to
        if ($request->getSession() instanceof SessionInterface) {
            $targetPath = $this->getTargetPath($request->getSession(), $providerKey);
        }

        if (!$targetPath) {
            $targetPath = $this->getDefaultSuccessRedirectUrl();
        }

        return new RedirectResponse($targetPath);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->getSession() instanceof SessionInterface) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        $url = $this->getLoginUrl();

        return new RedirectResponse($url);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $url = $this->getLoginUrl();

        return new RedirectResponse($url);
    }

    public function supportsRememberMe()
    {
        return true;
    }

    public function getDefaultSuccessRedirectUrl() {
        return $this->router->generate('easyadmin');
    }

    public function getLoginUrl() {
        return $this->router->generate('security_login');
    }

}
