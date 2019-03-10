<?php
	function parseSongData($level, $full = false) {
		$song_data = [];

		// $guitar_audio = file_exists('./songs/' . $level . '/guitar.ogg')
		// 	? file_get_contents('./songs/' . $level . '/guitar.ogg')
		// 	: file_get_contents('./songs/' . $level . '/guitar.mp3');
		// $notes_mid = file_get_contents('./songs/' . $level . '/notes.mid');
		$song_ini = file_get_contents('./songs/' . $level . '/song.ini');

		$song_data = [];
		foreach (explode("\n", $song_ini) as $song_line) {
			$song_line = trim($song_line);
			if (empty($song_line) || substr($song_line, 0, 1) == '[') {
				continue;
			}

			$line_data = explode(' = ', $song_line);

			if (count($line_data) < 2) {
				continue;
			}

			$song_data[strtoupper($line_data[0])] = trim($line_data[1]);
		}

		// $song_data['audio'] = $guitar_audio;
		$song_data['audio_path'] = file_exists('./songs/' . $level . '/guitar.ogg')
			? './songs/' . $level . '/guitar.ogg'
			: './songs/' . $level . '/guitar.mp3';
		// $song_data['mid'] = $notes_mid;
		$song_data['ogg_path'] = './songs/' . $level . '/guitar.ogg';
		$song_data['mid_path'] = './songs/' . $level . '/notes.mid';
		$song_data['ini_path'] = './songs/' . $level . '/song.ini';
		$song_data['label_path'] = (file_exists('./songs/' . $level . '/album.PNG')
			? 'songs/' . $level . '/album.PNG'
			: (file_exists('./songs/' . $level . '/album.png')
				? 'songs/' . $level . '/album.png'
				: ''
			)
		);

		$duration_raw = shell_exec('mediainfo --Inform="Audio;%Duration/String3%" \'' . str_replace('./', '', $song_data['audio_path']) . '\'');

		$duration_raw_pieces = explode('.', $duration_raw);

		$sec_dec = 0;
		$secs = 0;

		if (count($duration_raw_pieces) == 2) {
			$sec_dec = (int)$duration_raw_pieces[1];
			$song_data['duration_raw_to_seconds'] = $duration_raw_pieces[0];
			$duration_raw_pieces = array_reverse(explode(':', $duration_raw_pieces[0]));
		} else {
			$duration_raw_pieces = array_reverse(explode(':', $duration_raw));
			$song_data['duration_raw_to_seconds'] = $duration_raw;
		}

		if (substr($song_data['duration_raw_to_seconds'], 0, 3) === '00:') {
			$song_data['duration_raw_to_seconds'] = substr($song_data['duration_raw_to_seconds'], 3);
		}

		$multiplier = 1;

		foreach ($duration_raw_pieces as $duration_raw_piece) {
			$secs += (int)($duration_raw_piece * $multiplier);
			$multiplier *= 60;
		}

		$duration = (float)($secs . '.' . $sec_dec);
		$song_data['duration'] = $duration;
		$song_data['duration_raw'] = $duration_raw;

		$song_data['guitar_track'] = true;
		$song_data['bassguitar_track'] = isset($song_data['DIFF_BASS']) ? true : false;
		$song_data['drums_track'] = isset($song_data['DIFF_DRUMS']) ? true : false;

		$song_data['article_title'] = implode(
			"\n",
			[
				$song_data['ARTIST'] . ' - ' . $song_data['NAME'],
				'(' . $song_data['duration_raw_to_seconds'] . ')'
			]
		);

		return $song_data;
	}

	$level = isset($_GET['level']) ? $_GET['level'] : '';
	$instrument = isset($_GET['instrument']) ? $_GET['instrument'] : 'PART GUITAR';

	if (!in_array($instrument, [ 'PART GUITAR', 'PART BASS', 'PART DRUMS' ])) {
		$instrument = 'PART GUITAR';
	}

	$song_data = [];
	$list_songs = false;

	if (empty($level)
			|| !(file_exists('./songs/' . $level . '/guitar.ogg') || file_exists('./songs/' . $level . '/guitar.mp3'))
			|| !file_exists('./songs/' . $level . '/notes.mid')
			|| !file_exists('./songs/' . $level . '/song.ini')) {

		$songs = preg_grep('/^([^.])/', scandir('./songs'));
		$list_songs = true;
	} else {
		$song_data = parseSongData($level);
	}
?>
<!doctype HTML>
<html>
	<head>
		<title>TableHero</title>
		<script
			src="http://code.jquery.com/jquery-3.2.1.min.js"
			integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
			crossorigin="anonymous"></script>
		<script src="./js/MidiConvertRaw.js"></script>
		<link href="https://fonts.googleapis.com/css?family=New+Rocker" rel="stylesheet">
		<style>
			body {
				font-family: 'New Rocker', cursive;
				margin: 0;
				padding: 0;
				overflow: hidden;
				background: #2a2a2a;
				background-image: radial-gradient(circle, #5a5a5a, #4a4a4a, #3a3a3a, #2a2a2a, #1a1a1a);
			}
			table, table tr, table td {
				border-collapse: collapse;
				box-sizing: border-box;
				position: relative;
				padding: 0;
				margin: 0;
			}
			.hidden {
				display: none;
			}
			table tr:before {
				content: attr(data-calctop);
				position: absolute;
				top: 0;
				left: -15px;
				color: black;
			}
			#game-container {
				position: relative;
				margin: auto;
				width: 100%;
				height: 100vh;
				z-index: 5;
			}
			#song-container {
				position: relative;
				margin: auto;
				margin-top: 40px;
				width: 100%;
				height: 60%;
				z-index: 10;
			}
				/*#song-container td:after {
					content: '';
					position: absolute;
					display: inline-block;
					top: 0;
					bottom: 0;
					left: 50%;
					width: 2px;
					height: 100%;
					margin-left: -1px;
					background: #444;
					z-index: 1;
				}*/
			.button {
				display: inline-block;
				box-sizing: border-box;
				left: 0;
				border-radius: 50%;
				width: 32px;
				height: 32px;
				background: rgba(0, 0, 0, 0.4);
				border-width: 4px;
				z-index: 10;
			}
			.tail {
				display: inline-block;
				box-sizing: border-box;
				left: 24px;
				border-radius: 12%;
				width: 8px;
				height: 32px;
				background: rgba(0, 0, 0, 0.4);
				border-width: 4px;
				z-index: 10;
			}
			.a {
				background: green;
			}
			.b {
				background: red;
			}
			.c {
				background: yellow;
			}
			.d {
				background: blue;
			}
			.e {
				background: pink;
			}
			.tail.hidden-tail {
				visibility: hidden;
			}
			#song-container {
				position: absolute;
				left: 0;
				width: 100%;
				background: #b98b36;
				box-sizing: border-box;
				border-right: 1px solid #615d56;
			}
				#song-container tr.guitar-key td {
					border-top: 1px solid #615d56;
				}
				#song-container tr td {
					border-left: 1px solid #615d56;
					box-sizing: border-box;
				}
			#song {
				position: relative;
			}
				#song tr {
					width: 100%;
				}
				#song td {
					height: 32px;
					position: relative;
					width: 100px;
					text-align: center;
				}
			#keypressed {
				position: absolute;
				bottom: 0;
				width: 100%;
				box-sizing: border-box;
				/*border-top: 2px solid rgba(0, 0, 0, 0.4);*/
				z-index: 999;
			}
				#keypressed:before {
					content: '';
					position: absolute;
					display: inline-block;
					left: 0;
					right: 0;
					border-top: 2px solid rgba(0, 0, 0, 0.4);
					top: 50%;
					z-index: 2;
				}
			#keypressed > div {
				position: relative;
				display: inline-block;
				float: left;
				width: 20%;
				height: 32px;
				margin: 0;
				text-align: center;
				padding: 0 0;
				z-index: 6;
			}
				#keypressed > div > div {
					position: absolute;
					display: inline-block;
					top: 10%;
					left: 10%;
					width: 80%;
					height: 80%;
					opacity: 0.4;
					box-sizing: border-box;
					border: 3px solid;
					color: transparent;
					background: white!important;
					border-radius: 5%;
				}
					#keypressed > div > div.a {
						border-color: green;
					}
					#keypressed > div > div.b {
						border-color: red;
					}
					#keypressed > div > div.c {
						border-color: yellow;
					}
					#keypressed > div > div.d {
						border-color: blue;
					}
					#keypressed > div > div.e {
						border-color: pink;
					}
				#keypressed > div.pressed > div {
					opacity: 0.8;
				}
				#keypressed > div.glow > div {
					opacity: 1;
				}
					#keypressed > div.glow > div.a {
						background: green!important;
						box-shadow: 0px 0px 20px 10px green;
					}
					#keypressed > div.glow > div.b {
						background: red!important;
						box-shadow: 0px 0px 20px 10px red;
					}
					#keypressed > div.glow > div.c {
						background: yellow!important;
						box-shadow: 0px 0px 20px 10px yellow;
					}
					#keypressed > div.glow > div.d {
						background: blue!important;
						box-shadow: 0px 0px 20px 10px blue;
					}
					#keypressed > div.glow > div.e {
						background: pink!important;
						box-shadow: 0px 0px 20px 10px pink;
					}
					#keypressed > div.picked > div.a {
						background: green!important;
					}
					#keypressed > div.picked > div.b {
						background: red!important;
					}
					#keypressed > div.picked > div.c {
						background: yellow!important;
					}
					#keypressed > div.picked > div.d {
						background: blue!important;
					}
					#keypressed > div.picked > div.e {
						background: pink!important;
					}
			h1, h2, h3, h4 {
				position: absolute;
				left: 0;
				width: 100%;
				display: inline-block;
				text-align: left;
				font-size: 3em;
				color: #3eea0a;
				text-shadow:
					-3px -3px 0 #000,
					3px -3px 0 #000,
					-3px 3px 0 #000,
					3px 3px 0 #000;
				padding: 15px 0 0 15px;
				margin: 0;
				z-index: 9;
			}
			h1 {
				top: 0;
				font-size: 2.5em;
			}
			h2 {
				top: 64px;
				font-size: 2em;
			}
			h3 {
				top: 112px;
				font-size: 1.5em;
			}
			h4 {
				text-align: center;
				top: 35%;
				left: 50%;
				transform: translateX(-50%);
				font-size: 3.5em;
				z-index: 3;
			}
				h4 > a {
					text-decoration: none;
					color: inherit;
				}
			#wrapper {
				width: 60%;
				max-width: 500px;
				height: 100%;
				margin: auto;
				position: relative;
			}
			#play-button {
				position: absolute;
				display: inline-block;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				text-align: center;
				display: none;
			}
			#play-button > div {
				position: absolute;
				top: 35%;
				left: 50%;
				margin-left: -50px;
				width: 100px;
				font-size: 3.5em;
				color: #3eea0a;
				text-shadow:
					-3px -3px 0 #000,
					3px -3px 0 #000,
					-3px 3px 0 #000,
					3px 3px 0 #000;
				cursor: pointer;
				z-index: 5;
			}
			#score-container {
				position: fixed;
				font-size: 2.5em;
				right: 0;
				top: 0;
				padding: 0 25px;
				text-align: right;
				color: #3eea0a;
				text-shadow:
					-3px -3px 0 #000,
					3px -3px 0 #000,
					-3px 3px 0 #000,
					3px 3px 0 #000;  
			}
			/* loading screen */
			#loading-screen {
				display: inline-block;
				position: absolute;
				background: #2a2a2a;
				background-image: radial-gradient(circle, #5a5a5a, #4a4a4a, #3a3a3a, #2a2a2a, #1a1a1a);
				z-index: 99;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
			}
			#loading-screen-table {
				margin: auto;
				top: 0;
				height: 100%;
				left: 50%;
				transform: translate(-50%);
			}
				#loading-screen-table {
					width: 250px;
					height: 100%;
					margin-left: -25px;
				}
				#loading-screen-table td {
					width: 50px;
					height: 100%;
					position: relative;
				}
					@keyframes tremble {
						0% { margin-left: 0; }
						35% { margin-left: 0; }
						40% { margin-left: -6px; }
						45% { margin-left: 6px; }
						50% { margin-left: -5px; }
						55% { margin-left: 5px; }
						60% { margin-left: -4px; }
						65% { margin-left: 4px; }
						70% { margin-left: -3px; }
						75% { margin-left: 3px; }
						80% { margin-left: -2px; }
						85% { margin-left: 2px; }
						90% { margin-left: -1px; }
						85% { margin-left: 1px; }
					}
					#loading-screen-table td:before {
						content: '';
						position: absolute;
						display: inline-block;
						width: 2px;
						height: 100%;
						margin-left: 0;
						top: 0;
						left: 50%;
						animation-duration: 1s;
						animation-iteration-count: infinite;
						animation-timing-function: ease-in-out;
						animation-direction: alternate;
						animation-name: tremble;
					}
					#loading-screen-table td.ls-a-chord:before {
						background: green!important;
						animation-delay: 1s;
					}
					#loading-screen-table td.ls-b-chord:before {
						background: red!important;
						animation-delay: 3s;
					}
					#loading-screen-table td.ls-c-chord:before {
						background: yellow!important;
						animation-delay: 4s;
					}
					#loading-screen-table td.ls-d-chord:before {
						background: blue!important;
						animation-delay: 2s;
					}
					#loading-screen-table td.ls-e-chord:before {
						background: pink!important;
						animation-delay: 5s;
					}
					#loading-screen > div#loading-text {
						position: fixed;
						font-size: 3.5em;
						left: 0;
						bottom: 45%;
						width: 100%;
						padding: 0;
						text-align: center;
						color: #3eea0a;
						text-shadow:
							-3px -3px 0 #000,
							3px -3px 0 #000,
							-3px 3px 0 #000,
							3px 3px 0 #000;
						z-index: 99;
					}
				/* songs list */
				section {
					position: relative;
					margin-top: 64px;
					padding: 25px;
				}
				section > div {
					position: relative;
					float: left;
					width: 33%;
					overflow: hidden;
					padding: 20px 25px 0 0;
					margin: 0;
					box-sizing: border-box;
				}
					section > div:nth-child(3n) {
						padding-right: 0;
					}
				article {
					position: relative;
					display: inline-block;
					width: 100%;
					height: 150px;
					border-radius: 12px;
					overflow: hidden;
					box-sizing: border-box;
					opacity: 0.75;
					background-color: rgba(255, 255, 255, 0.075);
					transition: all 0.25s;
				}
					article a {
						color: #3eea0a;
						text-decoration: none;
					}
						article .title a:hover {
							text-decoration: underline;
						}
					article > a {
						display: inline-block;
						position: relative;
						width: 100%;
						height: 100%;
					}
					article:hover {
						background-color: rgba(255, 255, 255, 0.7);
						opacity: 1;
					}
					article img {
						position: absolute;
						width: 150px;
						height: 150px;
						left: 0;
						top: 0;
					}
					article .description {
						position: absolute;
						display: inline-block;
						top: 0;
						right: 0;
						bottom: 0;
						padding: 10px;
						text-align: right;
						color: #2eda0a;
						text-shadow:
							-2px -2px 0 #000,
							2px -2px 0 #000,
							-2px 2px 0 #000,
							2px 2px 0 #000;
					}
						article .description > .title {
							font-size: 1.5em;
						}
						article .description > .band {
							font-size: 1.25em;
						}
						article .description > .duration {
							font-size: 1.15em;
						}
						article .description > .album,
						article .description > .year {
							font-size: 1em;
						}
						article .description > .genre {
							font-size: 0.8em;
						}
					article .tracks {
						position: absolute;
						left: 160px;
						bottom: -42px;
						font-size: 2em;
						transition: all 0.25s;
					}
						article:hover .tracks {
							top: auto;
							bottom: 0;
						}
						article .tracks > a {
							text-decoration: none;
							background: none!important;
							text-shadow:
								-2px -2px 0 #000,
								2px -2px 0 #000,
								-2px 2px 0 #000,
								2px 2px 0 #000;
						}
							article .tracks .bassguitar-track {
								filter: hue-rotate(80deg);
								transform: scale(-1, 1);
								display: inline-block;
							}
				footer {
					height: 25px;
					width: 100%;
					display: inline-block;
					position: relative;
				}
		</style>
		<style id="css-duration">
		</style>
	</head>
<?php
	if ($list_songs) {
?>
	<body style="overflow: auto!important">
		<h1 style="text-align: center;font-size: 4em;font-style: italic;">TABLE HERO</h1>
		<section>
<?php
		foreach ($songs as $song) {
			if (substr($song, 0, 1) === '.') {
				continue;
			}

			$song_data = parseSongData($song);
?>
			<div>
				<article title="<?= $song_data['article_title'] ?>">
					<a href="?level=<?= $song ?>">
						<img src="<?= $song_data['label_path'] ?>"/>
					</a>
					<div class="description">
						<div class="title">
							<a href="?level=<?= $song ?>">
								<?= $song_data['NAME'] ?>
							</a>
						</div>
						<div class="band"><?= $song_data['ARTIST'] ?></div>
						<div class="duration"><?= $song_data['duration_raw_to_seconds'] ?></div>
					<?php
						if (isset($song_data['ALBUM'])) {
					?>
						<div class="album">Album: <?= $song_data['ALBUM'] ?></div>
					<?php
						}
					?>

					<?php
						if (isset($song_data['GENRE'])) {
					?>
						<div class="genre">Genre: <?= $song_data['GENRE'] ?></div>
					<?php
						}
					?>

					<?php
						if (isset($song_data['YEAR'])) {
					?>
						<div class="year">Year: <?= $song_data['YEAR'] ?></div>
					<?php
						}
					?>

					</div>
					<div class="tracks">
						<a title="Guitar Track" class="guitar-track" href="?level=<?= $song ?>">🎸</a>
						<?php
							if ($song_data['bassguitar_track']) {
						?>
						&nbsp;
						<a title="Bass Guitar Track" class="bassguitar-track" href="?level=<?= $song ?>&instrument=PART BASS">🎸</a>
						<?php
							}
						?>
						<?php
							if ($song_data['bassguitar_track']) {
						?>
						&nbsp;
						<a title="Drums Track" class="drums-track" href="?level=<?= $song ?>&instrument=PART DRUMS">🥁</a>
						<?php
							}
						?>
					</div>
				</article>
			</div>
<?php
		}
		if (empty($songs)) {
?>
			<h2 style="text-align: center">There are no songs</h2>
<?php
		}
?>
		</section>
		<footer>&nbsp;</footer>
	</body>
<?php
		die();
	} else {
?>
<?php
	}
?>
	<body>
		<audio id="song-audio" src="<?= $song_data['audio_path'] ?>">&nbsp;</audio>
		<audio id="midi-audio" src="<?= $song_data['mid_path'] ?>">&nbsp;</audio>
		<audio id="wrong-chord-1" src="sounds/WrongChord1.wav">&nbsp;</audio>
		<audio id="wrong-chord-2" src="sounds/WrongChord2.wav">&nbsp;</audio>
		<audio id="wrong-chord-3" src="sounds/WrongChord3.wav">&nbsp;</audio>
		<audio id="wrong-chord-4" src="sounds/WrongChord4.wav">&nbsp;</audio>
		<audio id="wrong-chord-5" src="sounds/WrongChord5.wav">&nbsp;</audio>
		<h1><?= $song_data['NAME'] ?></h1>
		<h2><?= $song_data['ARTIST'] ?></h2>
		<h3 id="duration"><?= $song_data['duration_raw_to_seconds'] ?></h3>
		<div id="hud">
			<div id="score-container">
				SCORE: <div id="score">0</div>
			</div>
		</div>
		<div id="game-container" data-level="<?= $level ?>" style="visibility: hidden;">
			<div id="wrapper">
				<h4>
					<a href="/">Songs List</a>
				</h4>
				<table id="song-container">
					<tbody id="song"></tbody>
				</table>
				<div id="keypressed">
					<div id="a"><div class="a">A</div></div>
					<div id="b"><div class="b">B</div></div>
					<div id="c"><div class="c">C</div></div>
					<div id="d"><div class="d">D</div></div>
					<div id="e"><div class="e">E</div></div>
				</div>
			</div>
		</div>
		<div id="play-button">
			<div>PLAY</div>
		</div>
		<div id="loading-screen">
			<div id="loading-text">Loading...</div>
			<table id="loading-screen-table">
				<tbody>
					<tr>
						<td class="ls-a-chord">&nbsp;</td>
						<td class="ls-b-chord">&nbsp;</td>
						<td class="ls-c-chord">&nbsp;</td>
						<td class="ls-d-chord">&nbsp;</td>
						<td class="ls-e-chord">&nbsp;</td>
					</tr>
				</tbody>
			</table>
		</div>
		<script type="text/javascript">
			const VISIBLE_ROWS = 70;
			const OVERLAP_TOLERANCE_START = 48;
			const OVERLAP_TOLERANCE_END = 10;
			const PRECISION_TOLERANCE = 150;

			const AUDIO_PATH = '<?= $song_data['audio_path'] ?>';
			const MIDI_PATH = '<?= $song_data['mid_path'] ?>';
			const INSTRUMENT = '<?= $instrument ?>';
			const SONG_DURATION = parseFloat('<?= $song_data['duration'] ?>');
      (function() {
      	let gameContainer = jQuery('#game-container');
      	let songContainer = jQuery('#song-container');
      	let songTable = jQuery('#song');
      	let cssDuration = jQuery('#css-duration');
    		let playButton = jQuery('#play-button');
    		let keypressed = jQuery('#keypressed');
    		let scoreElement = jQuery('#score');
    		let loadingScreen = jQuery('#loading-screen');

    		let durationTimer = jQuery('#duration');

    		let songDuration = -1;

    		let audio = new Audio(AUDIO_PATH);

    		let wrongChords = [
    			new Audio("sounds/WrongChord1.wav"),
    			new Audio("sounds/WrongChord2.wav"),
    			new Audio("sounds/WrongChord3.wav"),
    			new Audio("sounds/WrongChord4.wav"),
    			new Audio("sounds/WrongChord5.wav")
    		];

    		for (let i in wrongChords) {
    			wrongChords[i].loop = false;
    			wrongChords[i].volume = 0.3;
    		}

    		let keysTop = keypressed.position().top;
    		let keysBottom = keysTop + keypressed.height();

    		let bodyHeight = null;
    		let songContainerHeight = null;
    		let songTableHeight = null;

      	let song = {};

      	let score = 0;

      	let rand = function(min, max) {
					return Math.floor(Math.random() * max) + min;
				};

				let isPlayingWrongChord = false;

      	let playWrongChord = function() {
      		if (isPlayingWrongChord) {
      			return;
      		}
      		isPlayingWrongChord = true;
      		wrongChords[rand(0, 5)].play();
      		setTimeout(function() {
      			isPlayingWrongChord = false;
      		}, 1000);
      	};

      	let songRows = [];

      	let Controller = {
      		keys : {
      			a:			{ element: jQuery('#a'),	pressed: false },						// F1
	      		b:			{ element: jQuery('#b'),	pressed: false },						// F2
	      		c:			{ element: jQuery('#c'),	pressed: false },						// F3
	      		d:			{ element: jQuery('#d'),	pressed: false },						// F4
	      		e:			{ element: jQuery('#e'),	pressed: false }						// F5
      		},
      		pick:		{ instant: null,					pressed: false }						// pick
      	};

      	let setupController = function() {
      		let updateKey = function(k, pressed) {
	        	switch (k) {
	        		case 49:
	        		case 112:
	        			Controller.keys.a.pressed = pressed;
	        			break;
	        		case 50:
							case 113:
								Controller.keys.b.pressed = pressed;
								break;
							case 51:
							case 114:
								Controller.keys.c.pressed = pressed;
								break;
							case 52:
							case 115:
								Controller.keys.d.pressed = pressed;
								break;
							case 53:
							case 116:
								Controller.keys.e.pressed = pressed;
								break;
							case 32:
							case 13:
								if (pressed && !Controller.pick.pressed) {
									Controller.pick.instant = Date.now() - song.beginTime;
								} else {
									Controller.pick.instant = null;
								}
								Controller.pick.pressed = pressed;
								break;
	        	}
	      	};
	        jQuery(window).on('keydown', function(e) {
	        	updateKey(e.which || e.keyCode, true);
	        });
	        jQuery(window).on('keyup', function(e) {
	        	updateKey(e.which || e.keyCode, false);
	        });
      	};

      	let keyGlows = {
      		a: false,
      		b: false,
      		c: false,
      		d: false,
      		e: false
      	};

      	let drawKeys = function() {
      		let picked = Controller.pick.pressed;
      		for (let i in Controller.keys) {
    				let key = Controller.keys[i];
						if (key.pressed) {
							if (picked) {
      					key.element.addClass('picked');	
      				}
    					key.element.addClass('pressed');
    				} else {
    					key.element.removeClass('pressed');
    				}
    				if (keyGlows[i]) {
    					key.element.addClass('glow');
    				} else {
    					key.element.removeClass('glow');
    				}
    				if (!picked) {
    					key.element.removeClass('picked');
    				}
    			}
      	};

      	let getRefFromTime = function(timeInfoRaw) {
      		if (timeInfoRaw == null) {
      			return {
      				ref: '0-0',
      				ta: []
      			};
      		}
      		let timeInfoInt = (timeInfoRaw / 1000)|0;
    			let timeInfoDec = ((timeInfoRaw % 1000) / 100)|0;
    			return {
    				raw: timeInfoRaw,
    				ref: timeInfoInt + '-' + timeInfoDec
    			};
      	};

      	let getTimesDiff = function(time1, time2) {
      		return Math.abs(time1 - time2);
      	};

      	let checkTolerance = function(time1, time2) {
      		return getTimesDiff(time1, time2) < PRECISION_TOLERANCE;
      	};

      	let mapRow = function(row) {
      		let map = {};
      		let dataTop = parseFloat(row.attr('data-top'));
      		let dataBottom = parseFloat(row.attr('data-bottom'));
      		let rowTop = row.offset().top;
      		let rowBottom = rowTop + row.height();
      		row.find('td').each(function() {
      			let th = jQuery(this);
      			map[th.attr('data-key')] = {
      				element: th,
      				dataTop: dataTop,
      				dataBottom: dataBottom,
      				top: rowTop,
      				bottom: rowBottom,
      				button: th.children().is('.button'),
      				tail: th.children().is('.tail')
      			};
      			th.attr('data-calctop', rowTop);
      		});
      		return map;
      	};

      	let getPrecision = function(time1, time2) {
					return ((Math.max(getTimesDiff(time1, time2), 1) * 100) / PRECISION_TOLERANCE)|0;
      	};

      	let scorePoints = function(pointsType, precision) {
      		let scoreInc = (pointsType === 'pick')
      			? precision
      			: (pointsType === 'tail')
      				? 1
      				: (pointsType === 'error')
      					? -5
      					: 0;
      		score += scoreInc;
      	};

      	let pickFix = {
    			a: 0,
      		b: 0,
      		c: 0,
      		d: 0,
      		e: 0
    		};

    		let errors = {
    			a: 0,
      		b: 0,
      		c: 0,
      		d: 0,
      		e: 0
    		};

      	let pickedKeys = function(stepRef, lastTwo) {
      		for (let ltIndex in lastTwo) {
      			let row = lastTwo[ltIndex];

      			let stepKeys = row.find('.button');

	      		let map = mapRow(row);

	      		let atLeastOneError = false;
	      		let atLeastOneChordError = false;

	      		for (let i in Controller.keys) {
	      			let key = Controller.keys[i];

	      			let keyCheck = map[i];

	      			if (keyCheck.button) {
	      				// note is currently in place
		      			if (keyCheck.bottom + OVERLAP_TOLERANCE_START > keysTop && keyCheck.top - OVERLAP_TOLERANCE_END < keysBottom) {
									if (Controller.keys[i].pressed
		      						&& Controller.pick.pressed
		      						&& checkTolerance(Controller.pick.instant, stepRef.raw)) {

										scorePoints('pick', getPrecision(Controller.pick.instant, stepRef.raw));
										keyGlows[i] = true;
										pickFix[i] = 1;

									} else {

										// error
										keyGlows[i] = false;
										atLeastOneError = true;

									}
		      			}
		      		} else if (keyCheck.tail) {
								// tail is currently in place
								if (Controller.keys[i].pressed && keyGlows[i]) {

									// tail point
									scorePoints('tail');
									keyGlows[i] = true;

								} else {

									// error
									keyGlows[i] = false;
									atLeastOneError = true;

								}
	      			} else {
	      				keyGlows[i] = false;

	      				if (keyCheck.bottom + OVERLAP_TOLERANCE_START > keysTop && keyCheck.top - OVERLAP_TOLERANCE_END < keysBottom) {
	      					if (Controller.keys[i].pressed
		      						&& Controller.pick.pressed
		      						&& checkTolerance(Controller.pick.instant + pickFix[i], stepRef.raw)) {

	      						// error
										atLeastOneError = true;
										atLeastOneChordError = true;
										errors[i] = 1;

									} else {
										pickFix[i] = 0;
									}
	      				}
	      			}
	      		}

	      		if (atLeastOneError) {
		      		if (atLeastOneChordError) {
		      			let playError = false;
		      			for (let errIndex in errors) {
		      				if (errors[errIndex] > 0 && errors[errIndex] - pickFix[errIndex]) {
		      					playError = true;
		      				}
		      			}
		      			if (playError) {
		      				scorePoints('error');
		      				playWrongChord();
		      				for (let errIndex in errors) {
		      					errors[errIndex] = 0;
		      					pickFix[errIndex] = 0;
			      			}
		      			}
		      		}
	      		}
					}
      	};

      	let gameLoop = function() {
      		let step = -1;
      		let songRowsLength = songRows.length;

      		let gameLoopRef = setInterval(
	      		function() {
	      			step += 1;

	      			if (songRowsLength < 3) {
	      				clearInterval(gameLoopRef);
	      				return;
	      			}

	      			let stepRef = getRefFromTime(Date.now() - song.beginTime);

	      			// clean up
	      			let lr = songRows[songRowsLength - 1];
	      			while (lr.offset().top > keysBottom) {
	      				let toRemove = songRows.splice(-1)[0];
	      				songRowsLength -= 1;
	      				lr = songRows[songRowsLength - 1];
	      				songTable.find('tr#' + toRemove.attr('id')).remove();
	      				if (songRowsLength > VISIBLE_ROWS) {
	      					songTable.prepend(songRows[songRowsLength - VISIBLE_ROWS]);
	      				}
	      			}

	      			let last = songRows[songRowsLength - 1];
	      			let secondLast = songRows[songRowsLength - 2];
	      			let thirdLast = songRows[songRowsLength - 3];

	      			let lastTwo = [];

	      			if (last.offset().top > keysBottom) {
	      				let toRemove = songRows.splice(-1)[0];
	      				songRowsLength -= 1;
	      				lastTwo.push(secondLast);
	      				lastTwo.push(thirdLast);
	      				songTable.find('tr#' + toRemove.attr('id')).remove();
	      				if (songRowsLength > VISIBLE_ROWS) {
	      					songTable.prepend(songRows[songRowsLength - VISIBLE_ROWS]);
	      				}
	      			} else {
	      				lastTwo.push(last);
	      				lastTwo.push(secondLast);
	      			}

	      			pickedKeys(stepRef, lastTwo);

	      			drawKeys();
	      			scoreElement.text(score);
	      		},
	      		10 // 1000 / 10 / 10
	      	);
      	};

        let prepareSong = function(totalRows) {
        	setupController();

        	let startPosition = songContainer.height();

        	let duration = SONG_DURATION;

					cssDuration.append('#song-container { transition: all linear ' + (duration - 2) + 's; }');
					cssDuration.append('#song-container { bottom: 0; }');
					cssDuration.append('#song-container.start { bottom: -' + startPosition + 'px; }');

        	setTimeout(function() {
        		gameContainer.css('visibility', 'visible');
						playButton.on('click', function() {
							playButton.hide();
	        		songContainer.addClass('start');
	        		song.beginTime = Date.now();
	        		audio.play();
	        		bodyHeight = jQuery('body').height();
	        		songContainerHeight = songContainer.height();
	        		songTableHeight = songTable.height();
	        		gameLoop();

	        		let durationT = durationTimer.text().split(':');

							let durationMins = durationT[0]|0;
							let durationSecs = durationT[1]|0;

							let updateTimer = setInterval(
								function() {
									durationSecs -= 1;
									if (durationSecs <= 0) {
										durationMins -= 1;
										durationSecs = 59;
									}
									durationTimer.text(
										('00' + durationMins).substr(-2)
										+ ':'
										+ ('00' + durationSecs).substr(-2)
									);
									if (durationMins == 0 && durationSecs == 0) {
										clearInterval(updateTimer);
									}
								},
								1000
							);
						});
						playButton.show();
						loadingScreen.fadeOut(250, function() {
							loadingScreen.remove();
						});

					}, 2500);
        };

        let newNote = function() {
					return { a: 0, b: 0, c: 0, d: 0, e: 0 };
      	};

      	audio.addEventListener('loadeddata', function() {
    			// songDuration = audio.duration;
    			songDuration = SONG_DURATION;

    			setTimeout(function() {
	        	MidiConvert.load(
							MIDI_PATH,
							function(midi) {
								song.bpm = midi.bpm;
								song.PPQ = midi.header.PPQ;
								song.startTime = midi.startTime;
								song.raw_duration = midi.duration;

								song.duration = songDuration;

								song.guitar_track = null;
								for (let i in midi.tracks) {
									let track = midi.tracks[i];
									if (track.name === INSTRUMENT) {
										song.guitar_track = track;
										break;
									}
								}
								if (song.guitar_track == null) {
									throw("No guitar track found");
								}
								let baseTr = jQuery('<tr></tr>');
								baseTr
									.append('<td class="button-a" data-key="a"></td>')
									.append('<td class="button-b" data-key="b"></td>')
									.append('<td class="button-c" data-key="c"></td>')
									.append('<td class="button-d" data-key="d"></td>')
									.append('<td class="button-e" data-key="e"></td>');

								song.keysInTime = {};

								let notes = [];
								let noteIndex = 0;
								let currNote = null;
								let currTime = -song.bpm;

								// let secondsParts = 10;
								let secondsParts = 10;
								let numSlots = (song.duration + 1)|0;
								// let numSlots = (song.duration)|0;
								let songSlots = [];
	
								for (let slotIndex = 0; slotIndex < numSlots; ++slotIndex) {
									for (let secondsPartsIndex = 0; secondsPartsIndex < secondsParts; ++secondsPartsIndex) {
										if (parseFloat(slotIndex + '.' + secondsPartsIndex) <= song.duration) {
											let tr = baseTr.clone();
											tr.attr('id', slotIndex + '-' + secondsPartsIndex);
											songTable.prepend(tr);	
										}
									}
								}

								songContainer.css('height', songContainer.height() + 'px');

								songTable.find('tr').each(function(rowIndex) {
									let el = jQuery(this);
									let top = el.position().top;
									let bottom = top + el.height();

									el.attr('data-top', top);
									el.attr('data-bottom', bottom);

									// el.css('top', top + 'px');
									// el.css('position', 'absolute');

									if (rowIndex % 5 == 0) {
										el.addClass('guitar-key');
									}

									songRows.push(el);
									// songRows.push(el.clone());
								});
	
								let mapNote = function(noteName) {
									switch (noteName) {
										case 'c':
											noteName = 'a';
											break;
										case 'd':
											noteName = 'b';
											break;
										case 'e':
											noteName = 'c';
											break;
										case 'f':
											noteName = 'd';
											break;
										case 'g':
											noteName = 'e';
											break;
										default:
											noteName = null;
									}
									return noteName;
								};

								let findTdNote = function(timeTrRef, noteName) {
									let tr = songTable.find('#' + timeTrRef);
									let td = tr.find('td.button-' + noteName);

									return td;
								};

								let addTails = function(noteInt, noteDec, noteEnd, noteName, atLeastOneTailPiece) {
									atLeastOneTailPiece = atLeastOneTailPiece || false;

									noteDec = (noteDec + 1) % 10;
									noteInt = (noteDec == 0) ? noteInt + 1 : noteInt;

									let noteCheck = parseFloat(noteInt + '.' + noteDec);

									if (noteCheck < noteEnd) {
										let timeTrRef = noteInt + '-' + noteDec;

										if (typeof song.keysInTime[timeTrRef] == 'undefined') {
											return;
										}

										song.keysInTime[timeTrRef][noteName] = 2;

										let td = findTdNote(timeTrRef, noteName);

										if (!td.is('.has-tail') && !td.is('.has-button')) {
											td.append('<div class="tail ' + noteName + '">&nbsp;</div>');
											td.addClass('has-tail');
											if (td.is('.has-hidden-tail')) {
												td.children('.hidden-tail').remove();
											}
										}

										addTails(noteInt, noteDec, noteEnd, noteName, true);
									} else if (atLeastOneTailPiece !== true) {
										for (let i = 0; i < 2; ++i) {
											let timeTrRef = (noteInt + i) + '-' + noteDec;

											// if (typeof song.keysInTime[timeTrRef] === 'undefined') {
											// 	break;
											// }

											song.keysInTime[timeTrRef][noteName] = 3;

											let td = findTdNote(timeTrRef, noteName);

											if (!td.is('.has-tail') && !td.is('.has-button')) {
												td.append('<div class="tail hidden-tail ' + noteName + '">&nbsp;</div>');
												td.addClass('has-hidden-tail');
											}
										}
									}
								};

								let sPlayItSafe = Math.max(song.duration, song.raw_duration);

								// for (let kIndex = 0; kIndex < (sPlayItSafe|0) + 1; ++kIndex) {
								for (let kIndex = 0; kIndex < (sPlayItSafe|0); ++kIndex) {
									for (let kkIndex = 0; kkIndex < 10; ++kkIndex) {
										let timeTrRef = kIndex + '-' + kkIndex;
										song.keysInTime[timeTrRef] = newNote();
									}
								}

								for (let noteIndex = 0; noteIndex < song.guitar_track.notes.length; ++noteIndex) {
									let note = song.guitar_track.notes[noteIndex];
									let noteInt = note.time|0;
									let noteDec = ((note.time % 1) * 10)|0;
									let timeTrRef = noteInt + '-' + noteDec;
									let noteName = (note.name + '').toLowerCase().substr(0, 1);
									noteName = mapNote(noteName);
									if (noteName == null) {
										continue;
									}

									let noteEnd = note.noteOff;

									let tr = songTable.find('#' + timeTrRef);
									let td = tr.find('td.button-' + noteName);

									if (!td.is('.has-button') && !td.is('.has-tail')) {
										if (typeof song.keysInTime[timeTrRef] === 'undefined') {
											continue;
										}
										song.keysInTime[timeTrRef][noteName] = 1;
										td.children().remove();
										td.append('<div class="button ' + noteName + '">&nbsp;</div>');
										td.addClass('has-button');
										if (td.is('.hidden-tail')) {
											td.removeClass('hidden-tail');
										}
									}

									addTails(noteInt, noteDec, noteEnd, noteName);
								}

								let totalRows = songTable.children('tr').length;

								songTable.children('tr').remove();

								for (let songRowIndex = 0; songRowIndex < songRows.length; ++songRowIndex) {
									songRows[songRowIndex].css('top', songRows[songRowIndex].attr('data-top') + 'px');
									songRows[songRowIndex].css('position', 'absolute');
								}

								for (let songRowSpan = 1; songRowSpan <= VISIBLE_ROWS; ++songRowSpan) {
									songTable.prepend(songRows[songRows.length - songRowSpan]);
								}

								prepareSong(totalRows);
							}
						);
	      	}, 1000);
				});
      })();
    </script>
	</body>
</html>