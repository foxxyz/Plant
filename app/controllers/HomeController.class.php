<?php

	/**
	 * HomeController.class.php
	 *
	 * @package plant_app
	 * @subpackage controllers
	 */
	 
	/**
	 * Home Controller
	 *
	 * Controls URLs paths in the website root. Contains basic login and logout actions
	 * to allow users to authenticate themselves.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2007, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_app
	 * @subpackage controllers
	 * @version 1.2
	 * @uses FRAMEWORK_NAME
	 * @uses FRAMEWORK_VERSION
	 * @uses REDIRECT_ON_LOGIN
	 * @uses SITE_DESCRIPTION Meta site description for search engines
	 * @uses SITE_KEYWORDS Meta keywords for search engines
	 * @uses SITE_MAIN_STYLESHEET Main site stylesheet
	 * @uses TEMPLATE_DEFAULT_FORMAT Default template format
	 */
	class HomeController extends Controller {
		
		/**
		 * Login action
		 *
		 * Allows users to authenticate themselves and continue to a secured page.
		 * If login_redirect is supplied via GET or POST, this action will forward to that
		 * path.
		 *
		 * @return void
		 */
		public function actionLogin() {
			
			// Check if user is already logged in
			if ($this->user->isLoggedIn()) Headers::redirect(config("REDIRECT_ON_LOGIN"));
			
			// Set title
			$this->setTitle("Log In");
			
			// If login submit
			if ($this->get("login_submit")) {
				// Set form validation rules
				$this->form->setRequirement("login_username", "required", "Username is required");
				$this->form->setRequirement("login_password", "required", "Password is required");
				
				// If the form validates, login the user
				if ($this->form->validate("")) {
					if (UserModel::login($this->get("login_username"), $this->get("login_password"), false)) {
						$this->user->setLoginCookie(false);
						// Redirect user to where he needs to go
						if ($redirectURL = $this->get("login_redirect")) Headers::redirect($redirectURL);
						else Headers::redirect(config("REDIRECT_ON_LOGIN"));
					}
					else $this->setErrorMessage("Username or password invalid! Try again.");
				}
				
			}
			
		}
		
		/**
		 * Logout action
		 *
		 * Allows users to log themselves out. Redirects back to the page called from.
		 *
		 * @return void
		 */
		public function actionLogout() {
			
			// Find refering page
			$referringPage = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "";
		
			// Check if this user is already logged out
			if (!$this->user->isLoggedIn()) Headers::redirect($referringPage);
			
			// Log user out
			if (!$this->user->logout()) throw new Exception("Couldn't log out user!");
			
			Headers::redirect($referringPage);
			
		}
		
		/**
		 * @see Controller::setProperties()
		 */
		protected function setProperties() {
		
			// Set meta tags
			$this->setMeta("generator", config("FRAMEWORK_NAME") . " " . config("FRAMEWORK_VERSION"));
			$this->setMeta("robots", "all");
			$this->setMeta("author", "Ivo KH Janssen, http://codedealers.com");
			$this->setMeta("copyright", "Copyright ©" . date("Y") . " Ivo KH Janssen, Code Dealers");
			if (config("SITE_KEYWORDS")) $this->setMeta("keywords", config("SITE_KEYWORDS"));
			if (config("SITE_DESCRIPTION")) $this->setMeta("description", config("SITE_DESCRIPTION"));
			
			// Set stylesheets
			$this->setStyleSheet(config("SITE_MAIN_STYLESHEET"));
			
			// Set templates
			$this->setTemplates("header,main-nav," . config("TEMPLATE_DEFAULT_FORMAT") . ",footer");
			
		}
	
	}
	
?>