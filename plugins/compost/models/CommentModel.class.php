<?php

	/**
	 * CommentModel.class.php
	 *
	 * @package plant_compost
	 * @subpackage models
	 */
	 
	/**
	 * Comment Model
	 *
	 * Stores comment in storage and implements oft used functions.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage models
	 * @version 1.2
	 */
	class CommentModel extends EditableModel {
		
		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"		=>	"id",
			"post_id"	=>	Array(
				"type"		=>	"int",
				"linkedModel"	=>	"post",
			),
			"author_id"	=>	Array(
				"type"		=>	"int",
				"linkedModel"	=>	"user",
			),
			"date_posted"	=>	"date_creation",
			"name"		=>	"string",
			"email"		=>	"string",
			"content"	=>	"longstring",
			"status"	=>	Array(
				"type"		=>	"enum",
				"values"	=>	Array("moderation","approved"),
			),
		);
		/**
		 * @see Model::$DBTable
		 * @var string Storage table
		 */		
		protected $DBTable = "comment";
		
		/**
		 * Get this comment's author (if present)
		 *
		 * @return bool|UserModel Comment author or FALSE if none present
		 * @uses Model::getLinkedModel()
		 */			
		public function getAuthor() {
			
			return $this->getLinkedModel("author_id");
			
		}
		
		/**
		 * Email accessor
		 *
		 * @return string Author's email
		 * @uses getAuthor()
		 * @uses UserModel::getEmail()
		 * @uses CommentModel::$email
		 */	
		public function getEmail() {
			
			if (isset($this->author_id) && $this->author_id != 0) {
				return $this->getAuthor()->getEmail();	
			}
			
			if (!isset($this->email)) return false;
			return $this->email;
			
		}
		
		/**
		 * Message accessor
		 *
		 * @param $format Format to return message [raw|formatted]
		 * @return string Comment message formatted according to $format
		 * @uses CommentModel::$content
		 * @uses Filter::it()
		 */	
		public function getMessage($format = "formatted") {
			
			if (!isset($this->content)) return false;
			$content = $this->content;
			
			switch ($format) {
				case "raw":
					return $content;
					break;
				case "formatted":
				default:
					// Convert links
					$content = Filter::it($content, "addparagraphs");
					$search = '%(?<!["\'=])([a-z]+://(?:[a-z0-9-]+\.)+[^"\'<>)]*)%';
					$replace = '<a href="${1}">${1}</a>';
					$content = preg_replace($search,$replace, $content);
					if ($format == "quotes") {
						$content = preg_replace("|^<p>|","<p>&#147;", $content);
						$content = preg_replace("|(<br/>)?\s*</p>$|","&#148;</p>", $content);
					}
					return $content;
			}
			
		}
		
		/**
		 * Name accessor
		 *
		 * @return string Name of comment author
		 * @uses getAuthor()
		 * @uses CommentModel::$author_id
		 * @uses CommentModel::$name
		 */			
		public function getName() {
		
			if (isset($this->author_id) && $this->author_id != 0) return $this->getAuthor()->getName();	
		
			if (!isset($this->name)) return false;
			return $this->name;
			
		}
		
		/**
		 * Get associated post this comment was posted on
		 *
		 * @return PostModel Post this comment was posted on
		 * @uses getLinkedModel()
		 */
		public function getPost() {
		
			return $this->getLinkedModel("post_id");
			
		}
		
		/**
		 * Date accessor
		 *
		 * @return string Date comment was posted formatted verbally
		 * @uses CommentModel::$date_posted
		 */
		public function getPostDate() {
		
			if (!isset($this->date_posted)) return false;
			return date("l, F jS, \a\\t g:i:s a", $this->date_posted);
			
		}
		
		/**
		 * Status accessor
		 *
		 * @return string Current status of this comment
		 * @uses CommentModel::$status
		 */
		public function getStatus() {
			
			if (!isset($this->status)) return false;
			return $this->status;
			
		}
		
		/**
		 * Get verbal time since post
		 *
		 * @return string Time since this comment was posted
		 * @uses CommentModel::$date_posted
		 */
		public function getTimeSincePost() {
		
			if (!isset($this->date_posted)) return false;
			
			$times = array(
				"year"		=>	31536000,
				"month"		=>	2592000,
				"week"		=>	604800,
				"day"		=>	86400,
				"hour"		=>	3600,
				"minute"	=>	60,
				"second"	=>	1,
			);
			
			$difference = time() - $this->date_posted;
			
			foreach($times as $time	=> $duration) {
				
				if ($difference > ($duration * 1.5)) {
					$amount = floor($difference / $duration);
					if ($amount > 1) $time .= "s";
					return $amount . " " . $time . " ago";
				}
				
			}
			
			return "less than one second ago";
			
		}
		
		/**
		 * Get permalink URL to comment
		 *
		 * @return string URL
		 * @uses CommentModel::getID()
		 * @uses CommentModel::getPost()
		 * @uses PostModel::getURL()
		 */
		public function getURL() {
		
			if (!$this->getPost()) return false;
			
			return $this->getPost()->getURL() . "#comment" . $this->getID();
			
		}
		
		/**
		 * Set comment status
		 *
		 * @param string $newStatus A valid status as specified in $DBCols
		 * @return bool TRUE on successful status update, FALSE otherwise
		 * @uses CommentModel::$status
		 * @uses Model::update()
		 */
		public function setStatus($newStatus) {
			
			$this->status = $newStatus;
			
			return $this->update();
			
		}
		
	}

?>