<?php


	class CharacterSheet {
	
		protected $_image				= null;
		protected $_character			= null;
		protected $_roles				= null;
		
		protected static $_sprites		= null;
		protected static $_spriteParts	= [];
		protected static $_colors		= [];
		
		const JOB_START_PADDING			= 5;		// 5 pixels below the header for row 1	
		const JOB_END_PADDING			= 0;		// 0 pixels below the bottom row for the footer
		const JOB_ROW_HEIGHT			= 10;		// 10 pixels per job row
		const JOB_ROW_TEXT_WIDTH		= 84;		// from start of text to level numbers

		const FONT_FILENAME				= 'images/xfont.ttf';
		const FONT_SIZE					= 6;		// font size in points (used for imagettftext)
		const FONT_LETTER_WIDTH			= 6;		// 5 + 1 pixels per character
		const FONT_LETTER_HEIGHT		= 9;		// 8 + 1 pixels


		public function __construct(StdClass $character) {
			// init image if we have not done so yet
			if (!static::$_sprites) {
				static::_initParts();
			}

			$this->_character	= $character;
			$this->_roles		= static::getCharacterJobData($character);

		}


		protected function _calculateRequiredDimensions() {

			// the width is just the width of the header.
			$horizontalSize	= static::$_spriteParts['header']['w'];

			// start with the size of the top and bottom...
			$verticalSize	= static::$_spriteParts['header']['h'];
			$verticalSize	+= static::$_spriteParts['footer']['h'];
			var_dump($verticalSize);
			// add in the padding...
			$verticalSize	+= static::JOB_START_PADDING;
			$verticalSize	+= static::JOB_END_PADDING;
			var_dump($verticalSize);

			// calculate how many rows we need
			$verticalSize	+= static::JOB_ROW_HEIGHT * $this->_calculateRequiredRows($this->_roles);
			var_dump($verticalSize);

			return ['w' => $horizontalSize, 'h' => $verticalSize];
		}



		protected function _calculateRequiredRows($roles) {
			// bite me
			$rowsLeft	= 0;
			$rowsRight	= 0;

			// the total rows is just how many entries fit in that column...
			$rowsLeft	= count($roles['tank']) + count($roles['healer']) + count($roles['dps']);
			$rowsRight	= count($roles['hand']) + count($roles['land']);

			// ... plus separators between non-empty sections.
			// (add one for every non-empty section, then remove one free section)
			// (the max() is because you can technically have two empty sections on the right and -1 isn't valid.)
			// (this is not needed because max() in the return will take care of -1 but whatever. i could just erase
			//  this comment and move on but sunk comment fallacy and all)
			$rowsLeft	+= max(0, (!empty($roles['tank']) + !empty($roles['healer']) + !empty($roles['dps'])) - 1);
			$rowsRight	+= max(0, (!empty($roles['hand']) + !empty($roles['land'])) - 1);

			// of course, whatever side is larger wins
			return max($rowsLeft, $rowsRight);
		}


		public function generate() {
			$size		= $this->_calculateRequiredDimensions();
			var_dump($size);
			$this->_createImage($size['w'], $size['h']);

			$this->_placeSprite("header",    0,    0);
			$this->_placeSprite("footer",    0,   -1);

			$innerSize	= $size['h'] - (static::$_spriteParts['header']['h'] + static::$_spriteParts['footer']['h']);
			$this->_placeSprite("inner",     0, static::$_spriteParts['header']['h'], null, $innerSize);

			$this->_drawText( 11,  7,  $this->_character->Character->Name);
			
			$rightAlign	= strlen($this->_character->Character->Server) * static::FONT_LETTER_WIDTH;
			$this->_drawText( $size['w'] - 10 - $rightAlign,  7,  $this->_character->Character->Server, "blue");
			$this->_placeSprite("home", $size['w'] - 20 - $rightAlign, 8);



			$initialY	= static::$_spriteParts['header']['h'] + static::JOB_START_PADDING;

			$this->_drawRoles(  16, $initialY, ['tank', 'healer', 'dps']);
			$this->_drawRoles( 138, $initialY, ['hand', 'land']);

		}


		protected function _drawRoles($startX, $startY, $roles) {
			$seperator	= false;
			$x			= $startX;
			$y			= $startY;

			foreach ($roles as $role) {
				if ($seperator) {
					$this->_placeSprite("seperator", $x - 9, $y + 2);
					$y	+= static::JOB_ROW_HEIGHT;
					$seperator	= false;
				}

				foreach ($this->_roles[$role] as $job) {
					imagefilledrectangle($this->_image, $x - 6, $y - ($seperator ? 2 : 0), $x - 4, $y + static::JOB_ROW_HEIGHT - 3, $this->_getColor("role$role"));
					$this->_drawText( $x, $y, $job['name']);

					$textWidth	= (strlen($job['name']) * static::FONT_LETTER_WIDTH);
					for ($dotsX = static::JOB_ROW_TEXT_WIDTH - static::FONT_LETTER_WIDTH + 1; $dotsX > $textWidth; $dotsX -= static::FONT_LETTER_WIDTH) {
						$this->_placeSprite("dots", $x + $dotsX, $y);
					}

					$levelText	= sprintf("%2d", $job['level']);
					$tempX		= $x + static::JOB_ROW_TEXT_WIDTH + 1;
					$numW		= static::$_spriteParts['num0']['w'] + 1;
					$nums		= ($job['level'] == 90 ? "numg" : "num");
					for ($i = 0; $i <= 1; $i++) {
						if ($levelText[$i] === " ") continue;
						$this->_placeSprite($nums . $levelText[$i], $tempX + $i * $numW, $y);
					}

					$y			+= static::JOB_ROW_HEIGHT;
					$seperator	= true;

				}
			}

		}





		public function save($filename) {
			imagepng($this->_image, $filename);
		}




		protected function _createImage($width, $height) {
			$this->_image	= imagecreatetruecolor($width, $height);
			imagesavealpha($this->_image, true);
			imagealphablending($this->_image, false);
			imagefilledrectangle($this->_image, 0, 0, $width, $height, imagecolorallocatealpha($this->_image, 255, 0, 255, 127));
		}

		/**
		 * Draws text onto the image. Origin point is top-left of where text should go
		 */
		protected function _drawText($x, $y, $text, $tColor = null) {
			$color	= $this->_getColor($tColor);
			
			imagettftext($this->_image, static::FONT_SIZE, 0, $x, $y + static::FONT_LETTER_HEIGHT - 2, $color, static::FONT_FILENAME, $text);
		}


		protected function _getColor($color = null) {
			if (is_array($color)) {
				return imagecolorallocate($this->_image, $color[0], $color[1], $color[2]);

			} elseif (is_string($color)) {
				if (!isset(static::$_colors[$color])) {
					throw new \Exception("invalid color string: $color");
				}
				$color	= static::$_colors[$color];
				return imagecolorallocate($this->_image, $color[0], $color[1], $color[2]);
			}
			
			return imagecolorallocate($this->_image, 255, 255, 255);

		}


		protected function _placeSprite($spriteName, $x, $y, $w = null, $h = null) {
			$sprite	= static::$_spriteParts[$spriteName];
			if (!$sprite) {
				throw new \Exception("unknown sprite $spriteName");
			}

			$width	= $w ?? $sprite['w'];
			$height	= $h ?? $sprite['h'];

			// if x or y is negative, act like we're starting from the other edge
			// e.g. "y = -1" -> "put this on the very bottom of the image"
			if ($x < 0) {
				$x	= imagesx($this->_image) - ($x + 1) - $width;
			}
			if ($y < 0) {
				$y	= imagesy($this->_image) - ($y + 1) - $height;
			}

			imagecopyresized($this->_image, static::$_sprites, $x, $y, $sprite['x'], $sprite['y'], $width, $height, $sprite['w'], $sprite['h']);
		}



		protected static function _initParts() {
			static::$_sprites		= imagecreatefrompng("images/background.png");
			
			static::_addSprite("header",       0,   0, 250,  19);
			static::_addSprite("inner",        0,  20, 250,   3);
			static::_addSprite("footer",       0,  25, 250,   5);
			
			static::_addSprite("seperator",    0,  31, 114,   3);
			
			for ($i = 0; $i <= 9; $i++) {
				static::_addSprite("num{$i}", 0 + 9 * $i, 35, 8, 7);
				static::_addSprite("numg{$i}", 0 + 9 * $i, 43, 8, 7);
			}
			static::_addSprite("dots", 90, 35, 5, 7);
			static::_addSprite("home", 96, 35, 7, 7);

			// colors for various things
			static::$_colors['text']		= [255, 255, 255];	// white text
			static::$_colors['blue']		= [153, 171, 215];	// light blue text
			static::$_colors['roletank']	= [153, 171, 215];	// tank bar
			static::$_colors['rolehealer']	= [177, 226, 141];	// healer bar
			static::$_colors['roledps']		= [222, 167, 160];	// dps bar
			static::$_colors['rolehand']	= [135, 107, 180];	// doh bar
			static::$_colors['roleland']	= [163, 147,  35];	// dol bar

		}

		protected static function _addSprite($name, $x, $y, $w, $h) {
			static::$_spriteParts[$name]	= [ 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h];
		}















		// you may be asking yourself a few questions, such as
		// 1. why are these static methods
		// 2. why are they like this
		// 3. why is this even in an object
		// they are, indeed, good questions.
		public static function getCharacterJobData(StdClass $character) {
			$jobs	= static::_getCharacterJobs($character);
			$roles	= static::_getCharacterJobsByRoles($jobs);

			return $roles;
		}

		protected static function _getCharacterJobs(StdClass $character) {
			$jobs	= [];
			foreach ($character->Character->ClassJobs as $job) {
	
				// first, is this even unlocked? do we care?
				if ($job->Level === 0) {
					// If this job is not unlocked, skip
					continue;
				}
	
				// Otherwise, arrange the data I guess
				// the awkward default is because blue mage UnlockedState->ID is null
				// also (Limited Job) why would you put that in the name, who cares
				$jobs[$job->UnlockedState->ID ?? $job->ClassID]	= [
					'name'		=> str_replace(" (Limited Job)", "", $job->UnlockedState->Name),
					'level'		=> $job->Level,
					'exp'		=> $job->ExpLevel,		// EXP done this level
					'expLevel'	=> $job->ExpLevelMax,	// Total EXP for next level
					'expNext'	=> $job->ExpLevelTogo,	// Remaining EXP for next level
					
					// IsSpecialized possibly also useful here if we ever expand this i guess?
					];
			}
	
			return $jobs;
		}

		protected static function _getCharacterJobsByRoles($characterJobs) {
			$roles	= [
					// the list here is for "[class], job";
					// some classes evolve into jobs,
					// while some jobs are effectively their own class as well
					// ... thus we have to check two ids sometimes and othertimes only one!
				'tank'	=> [
					 1, 19,  // gladiator / paladin
					 3, 21,  // marauder / warrior
					32,      // dark knight / dark knight
					37,      // gunbreaker / gunbreaker
					],
				'healer' => [
					 6, 24,  // conjurer / white mage
					28,      // arcanist* / scholar  (* arcanist is not a healer; it is a class shared with summoner)
					33,      // astrologian / astrologian
					40,      // sage / sage
					],
				'dps'	=> [
					// Melee
					 2, 20,  // pugilist / monk
					 4, 22,  // lancer / dragoon
					29, 30,  // rogue / ninja
					34,      // samurai / samurai
					39,      // reaper / reaper
					// Physical Ranged
					 5, 23,  // archer / bard
					31,      // machinist / machinist
					38,      // dancer / dancer
					// Magical Ranged
					 7, 25,  // thaumaturge / black mage
					26, 27,  // arcanist* / summoner  (* arcanist/summoner level is shared with scholar, above, i think)
					35,      // red mage / red mage
					36,      // blue mage / blue mage
					],
				'hand' => [
					 8,      // carpenter / carpenter
					 9,      // blacksmith / blacksmith
					10,      // armorer / armorer
					11,      // goldsmith / goldsmith
					12,      // leatherworker / leatherworker
					13,      // weaver / weaver
					14,      // alchemist / alchemist
					15,      // culinarian / culinarian
					],
				'land'	=> [
					16,      // miner / miner
					17,      // botanist / botanist
					18,      // fisher / fisher
				],
			];
	
			$charRoleJobs	= [];
			foreach ($roles as $role => $ids) {
				$charRoleJobs[$role]	= [];
	
				foreach ($ids as $id) {
					if ($characterJobs[$id]) {
						$charRoleJobs[$role][$id]	= $characterJobs[$id];
					}
				}
			}
	
			return $charRoleJobs;
	
		}


	}