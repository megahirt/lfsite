<?php

// This file is generated by a gulp task from WebsiteInstances.ejs and WebsiteInstances.json. Do not directly modify!

namespace Api\Library\Shared;

use Api\Model\Shared\Rights\SiteRoles;

class WebsiteInstances
{

    /**
     * This function contains the site "definitions" for Scripture Forge sites
     * @return array
     * @throws \Exception
     */
    public static function getScriptureForgeSites()
    {
        $sites = array();

        /*
         * **************************
         * SCRIPTURE FORGE WEBSITES
         * **************************
         */


        // scriptureforge.localhost sites
        $w = new Website('scriptureforge.localhost', Website::SCRIPTUREFORGE);
        $w->name = 'Scripture Forge';
        $w->ssl = false;
        $w->userDefaultSiteRole = SiteRoles::PROJECT_CREATOR;
        $w->releaseStage = 'local';
        $sites['scriptureforge.localhost'] = $w;

        $w = new Website('jamaicanpsalms.scriptureforge.localhost', Website::SCRIPTUREFORGE);
        $w->name = 'The Jamaican Psalms Project';
        $w->ssl = false;
        $w->theme = 'jamaicanpsalms';
        $w->defaultProjectCode = 'jamaican_psalms';
        $w->releaseStage = 'local';
        $sites['jamaicanpsalms.scriptureforge.localhost'] = $w;

        $w = new Website('demo.scriptureforge.localhost', Website::SCRIPTUREFORGE);
        $w->name = 'Scripture Forge';
        $w->ssl = true;
        $w->theme = 'simple';
        $w->userDefaultSiteRole = SiteRoles::PROJECT_CREATOR;
        $w->releaseStage = 'local';
        $sites['demo.scriptureforge.localhost'] = $w;

        // qa.scriptureforge.org
        $w = new Website('qa.scriptureforge.org', Website::SCRIPTUREFORGE);
        $w->name = 'Scripture Forge';
        $w->ssl = true;
        $w->userDefaultSiteRole = SiteRoles::PROJECT_CREATOR;
        $w->releaseStage = 'qa';
        $sites['qa.scriptureforge.org'] = $w;

        // scriptureforge.org
        $w = new Website('scriptureforge.org', Website::SCRIPTUREFORGE);
        $w->name = 'Scripture Forge';
        $w->ssl = true;
        $w->userDefaultSiteRole = SiteRoles::PROJECT_CREATOR;
        $w->isProduction = true;
        $w->releaseStage = 'live';
        $sites['scriptureforge.org'] = $w;

        // jamaicanpsalms.com
        $w = new Website('jamaicanpsalms.scriptureforge.org', Website::SCRIPTUREFORGE);
        $w->name = 'The Jamaican Psalms Project';
        $w->ssl = true;
        $w->theme = 'jamaicanpsalms';
        $w->defaultProjectCode = 'jamaican_psalms';
        $w->isProduction = true;
        $w->releaseStage = 'live';
        $sites['jamaicanpsalms.scriptureforge.org'] = $w;

        // waaqwiinaagiwritings.org
        $w = new Website('waaqwiinaagiwritings.org', Website::SCRIPTUREFORGE);
        $w->name = 'Waaqwiinaagi Writings';
        $w->ssl = true;
        $w->theme = 'simple';
        $w->defaultProjectCode = 'waaqwiinaagiwritings';
        $w->isProduction = true;
        $w->releaseStage = 'live';
        $sites['waaqwiinaagiwritings.org'] = $w;


        return $sites;
    }


    /**
     * This function contains the site "definitions" for Language Forge sites
     * @throws \Exception
     * @return array
     */
    public static function getLanguageForgeSites()
    {
        $sites = array();


        /*
         * **************************
         * LANGUAGE FORGE WEBSITES
         * **************************
         */


        // languageforge.localhost sites
        $w = new Website('languageforge.localhost', Website::LANGUAGEFORGE);
        $w->name = 'Language Forge';
        $w->ssl = false;
        $w->userDefaultSiteRole = SiteRoles::PROJECT_CREATOR;
        $w->releaseStage = 'local';
        $sites['languageforge.localhost'] = $w;

        // qa.languageforge.org
        $w = new Website('qa.languageforge.org', Website::LANGUAGEFORGE);
        $w->name = 'Language Forge';
        $w->ssl = true;
        $w->userDefaultSiteRole = SiteRoles::PROJECT_CREATOR;
        $w->releaseStage = 'qa';
        $sites['qa.languageforge.org'] = $w;

        // languageforge.org
        $w = new Website('languageforge.org', Website::LANGUAGEFORGE);
        $w->name = 'Language Forge';
        $w->ssl = true;
        $w->userDefaultSiteRole = SiteRoles::PROJECT_CREATOR;
        $w->isProduction = true;
        $w->releaseStage = 'live';
        $sites['languageforge.org'] = $w;


        return $sites;
    }


    /**
     * @return array
     */
    public static function getRedirects() {

        $redirects = array();

        $redirects['www.scriptureforge.org'] = 'scriptureforge.org';
        $redirects['www.languageforge.org'] = 'languageforge.org';
        $redirects['www.jamaicanpsalms.com'] = 'jamaicanpsalms.scriptureforge.org';
        $redirects['www.jamaicanpsalms.org'] = 'jamaicanpsalms.scriptureforge.org';
        $redirects['jamaicanpsalms.org'] = 'jamaicanpsalms.scriptureforge.org';
        $redirects['jamaicanpsalms.com'] = 'jamaicanpsalms.scriptureforge.org';

        return $redirects;
    }

}