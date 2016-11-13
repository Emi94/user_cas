<?php

/**
 * ownCloud - user_cas
 *
 * @author Sixto Martin <sixto.martin.garcia@gmail.com>
 * @author Felix Rupp <kontakt@felixrupp.com>
 *
 * @copyright Sixto Martin Garcia. 2012
 * @copyright Leonis. 2014 <devteam@leonis.at>
 * @copyright Felix Rupp <kontakt@felixrupp.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once __DIR__ . '/../vendor/phpCAS/CAS.php';

$app = new \OCA\User_CAS\AppInfo\Application();
$c = $app->getContainer();

if (\OCP\App::isEnabled($c->getAppName())) {

    $ocUserCas = \OCA\User_CAS\UserCAS::getInstance();

    \OCP\App::registerAdmin($c->getAppName(), 'admin');

    $urlGenerator = \OC::$server->getURLGenerator();

    \OC_App::registerLogIn(array('href' => $urlGenerator->linkToRoute('user_cas.authentication.login'), 'name' => 'CAS Login'));

    $forceLogin = $ocUserCas->isEnforceAuthentication();

    if($forceLogin) {

        $c->query('AuthenticationController')->login();
    }

    /*if ((isset($_GET['app']) && $_GET['app'] === $c->getAppName()) || $forceLogin) {

        if ($ocUserCas->isInitialized()) {

            phpCAS::forceAuthentication();

            $userName = phpCAS::getUser();

            $result = $ocUserCas->login($userName);

            if($isLoggedIn) {


            }

            if (isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"] !== 'app=user_cas') {
                header('Location: ' . \OC::$WEBROOT . '/?' . $_SERVER["QUERY_STRING"]);
                exit();
            }
        }

        \OC::$REQUESTEDAPP = '';
        \OC_Util::redirectToDefaultPage();
    }*/

    /*if (!phpCAS::isAuthenticated() && !\OCP\User::isLoggedIn()) {
        \OC_App::registerLogIn(array('href' => '?app=user_cas', 'name' => 'CAS Login'));
    }*/
}