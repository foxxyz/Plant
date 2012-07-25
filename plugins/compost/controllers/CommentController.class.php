<?php

	/**
	 * CommentController.class.php
	 *
	 * @package plant_compost
	 * @subpackage controllers
	 */
	 
	/**
	 * Comment Controller
	 *
	 * Controls basic functionality for actions/properties in the admin comment section
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage controllers
	 * @version 1.0
	 * @uses CommentModel
	 */
	class CommentController extends Controller {
		
		/**
		 * @see EditingController::$modelName
		 */
		protected $modelName = "comment";
		
		/**
		 * Approve a comment action
		 *
		 * Set a "method" parameter in the query string to "ajax" to get results of this action via AJAX
		 *
		 * @param int $modelID ID of the comment to approve
		 * @return void
		 */
		public function actionApprove($modelID) {
			
			// Only for admins
			if (!$this->user->isLoggedIn() || (!$this->user->is("editor") && !$this->user->isAdmin())) throw new PathNotFoundException("Not allowed");
		
			// Check arguments
			if (!is_numeric($modelID)) throw new PathNotFoundException("Invalid " . $this->getModelName() . " ID!");
			
			// Check if this model exists
			if (!$existingModel = Model::getByID($this->modelName, $modelID)) $this->setErrorMessage("Couldn't approve that " . $this->getModelName() . "... It doesn't exist!");
			// Okay, it's good, approve it
			elseif ($existingModel->setStatus("approved") && $existingModel->getPost()->updateComments()) {
				$this->setStatusMessage("Comment by " . $existingModel->getName() . " has been approved!");
			}
			
			if ($this->get("method") && $this->get("method") == "ajax") return $this->actionResults();
			else Headers::redirect($this->getPath());
			
		}
		
		/**
		 * Delete a comment action
		 *
		 * Set a "method" parameter in the query string to "ajax" to get results of this action via AJAX
		 *
		 * @param int|string $modelID ID of the comment to delete, or "allpending" to delete all pending comments
		 * @return void
		 */
		public function actionDelete($modelID) {
			
			// Only for admins
			if (!$this->user->isLoggedIn() || (!$this->user->is("editor") && !$this->user->isAdmin())) throw new PathNotFoundException("Not allowed");
			
			// Check arguments
			if (!is_numeric($modelID) && $modelID != "allpending") throw new PathNotFoundException("Invalid comment ID!");
			
			if ($modelID == "allpending") {
				$allPending = Model::getAll($this->modelName, "comment.status = 'moderation'");
				$success = true;
				if ($allPending) {
					foreach($allPending as $pendingComment) {
						$success = $success && $pendingComment->delete();
						$success = $success && $pendingComment->getPost()->updateComments();
					}
				}
				if ($success) $this->setStatusMessage("All pending comments deleted!");
				else $this->setErrorMessage("Not all pending comments could be deleted... manually remove what's left.");

				Headers::redirect($this->getPath());
			}
			
			// Check if this model exists
			if (!$existingModel = Model::getByID($this->modelName, $modelID)) {
				$this->setErrorMessage("Couldn't delete that comment... It doesn't exist!");
				Headers::redirect($_SERVER["HTTP_REFERER"]);
				die();
			}
			
			// Get associated item
			$commentItem = $existingModel->getPost();
			
			// Okay, it's good, delete it
			if ($existingModel->delete() && $commentItem->updateComments()) {
				$this->setStatusMessage("Comment by " . $existingModel->getName() . " successfully deleted.");
			}
			
			if ($this->get("method") && $this->get("method") == "ajax") return $this->actionResults();
			else Headers::redirect($this->getPath());
			
		}
		
		/**
		 * @see Controller::setProperties()
		 */
		protected function setProperties() {

			$this->setJavascript("admin-comments");

		}

		/**
		 * Results action
		 *
		 * Show results of an AJAX call
		 *
		 * @return void
		 */
		private function actionResults() {

			// Set templates
			$this->setTemplates("%controller%-%action%");
			$this->action = "results";

		}
		
	}
?>