<?php
require_once 'app/classes/User.php';
require_once 'app/classes/Room.php';
require_once 'app/classes/Game.php';
require_once 'app/classes/MessageSender.php';
require_once 'app/classes/core/logs/Log.php';
require_once 'app/classes/AnswerForms/UndefinedAnswer.php';
require_once 'app/classes/AnswerForms/TextAnswer.php';
require_once 'app/classes/AnswerForms/VoiceAnswer.php';
require_once 'app/classes/AnswerForms/PhotoAnswer.php';
require_once 'app/classes/AnswerForms/StickerAnswer.php';
require_once 'app/classes/AnswerForms/LeftParticipantAnswer.php';
require_once 'app/classes/AnswerForms/NewParticipantAnswer.php';

// require_once 'vendor/autoload.php';

use App\Classes\User;
use App\Classes\Room;
use App\Classes\Game;
use App\Classes\MessageSender;
use App\Classes\Core\Logs\Log;
use App\Classes\AnswerForms\UndefinedAnswer;
use App\Classes\AnswerForms\TextAnswer;
use App\Classes\AnswerForms\VoiceAnswer;
use App\Classes\AnswerForms\PhotoAnswer;
use App\Classes\AnswerForms\StickerAnswer;
use App\Classes\AnswerForms\LeftParticipantAnswer;
use App\Classes\AnswerForms\NewParticipantAnswer;

//Log instance creating
if (class_exists('App\Classes\Core\Logs\Log')) {
	Log::setRootLogDir('./logs');
	$log = new Log('/tgBot.log');
} else {
	die('Class existing Error!');
}

//Getting the secret token
$secretToken = file_get_contents(__DIR__."/init/token.txt");
if (class_exists('App\Classes\MessageSender')) {
	MessageSender::setBotToken($secretToken);
} else {
	die('Class existing Error!');
}

//Defining the template file prefix
define("TEMPL_PREFIX", __DIR__."/temp");

//Defining the ADMIN id file prefix to send him some user messages
define("ADMIN_ID", file_get_contents(__DIR__."/init/creator_id.txt"));

//Getting the main data
$data = file_get_contents('php://input');
if (empty($data)) {
	die('Class existing Error!');
}
$dataArray = json_decode($data, true);

//Getting the type of the main data: 'message' or 'edited_message'
$messageType = 'message';
if (array_key_exists('edited_message', $dataArray) ) {
	$messageType = 'edited_message';
}

//UserAnswer and Room instances creating
if (class_exists('App\Classes\Room') && class_exists('App\Classes\AnswerForms\UndefinedAnswer')) {
	$userAnswer = answerType($dataArray[$messageType]);
	$room = new Room($dataArray[$messageType]['chat']);
} else {
	$log->log('Class existing Error! In the line: ' . __LINE__);
	die('Class existing Error!');
}

//Complete file Paths according to this chat room
$settingsFilePath = TEMPL_PREFIX.'/settings_' . $room->roomIDPath() . '.txt';
$scoresFilePath = TEMPL_PREFIX.'/scores_' . $room->roomIDPath() . '.txt';
$translTrain_copyFilePath = TEMPL_PREFIX.'/translTraining_copy' . $room->roomIDPath() . '.txt';
$userMessageFilePath = TEMPL_PREFIX.'/message.txt';
$logFilePath = TEMPL_PREFIX.'/log.txt';
$errorsFilePath = TEMPL_PREFIX.'/errors.txt';

//Game and User instances creating
if (class_exists('App\Classes\Game') && class_exists('App\Classes\User') ) {
	$game = new Game($settingsFilePath, $translTrain_copyFilePath);
	$user = new User($dataArray[$messageType]['from'], $scoresFilePath);
} else {
	$log->log('Class existing Error! In the line: ' . __LINE__);
	die('Class existing Error!');
}

//Main dependancies
if ($userAnswer->getAnswerType() === 'undefined'){
	$respText = "Просьба отвечать текстом, " . $user->getFirstName() . ".";
} elseif ($userAnswer->getAnswerType() === 'voice') {
	$respText = "У вас прекрасный голос, " . $user->getFirstName() . "! Но я жду текстовый ответ.";

	// $newQueryArray = ['peer' => $userAnswer->getFileID(), 'msg_id' => $dataArray[$messageType]['message_id']];

	// $messageSender = new MessageSender($room->getID(), $respText);
	// $resp = $messageSender->setMethod('messages.transcribedAudio')->setQueryArray($newQueryArray)->sendMessage();
	// $dataewsr = json_decode($resp, true)['result']['text'];
	// $log->log(json_encode($dataewsr));

} elseif ($userAnswer->getAnswerType() === 'sticker') {
	$respText = $userAnswer->getEmoji();
} elseif ($userAnswer->getAnswerType() === 'photo') {
	$respText = "Надеюсь, на фото ответ, " . $user->getFirstName() . ". Но я понимаю только текст!";
} elseif ($userAnswer->getAnswerType() === "new_chat_participant"){
	$respText = "Γεια σας, " . $userAnswer->getFirstName() . "! Переведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
} elseif ($userAnswer->getAnswerType() === "left_chat_participant"){
	$respText = "Αντίο, " . $userAnswer->getFirstName() . ". Στο καλό!";
} elseif ($userAnswer->isCorrectAnswer($game->getTrueResponse()) || $userAnswer->getFormatUserAnswer() === $game->getTrueOption()) {
	$game->userWin();
	$user->setUserScore($userAnswer->getAnswerPrice());
	$respText = "Правильно, " . $user->getFirstName() . "! Ответ был: «{$game->getTrueResponse()}». \nВаш счёт = <b>" . $user->getUserScore() . "</b>.\n\nПереведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
} elseif (preg_match("/^[Пп]ожелание(.*?)/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = "Пожелание принято. Спасибо, " . $user->getFirstName() . "!";

	$messageSender = new MessageSender(ADMIN_ID, "<b>Пожелание</b> от пользователя " . $user->getFirstName() . " " . $user->getLastName() . "\n\n" . str_replace(['Пожелание', 'пожелание'], '', $userAnswer->getUserAnswer()));
	$messageSender->sendMessage();

	$desireLog = new Log('/User_Desires.log');
	$desireLog->log("--- " . $user->getFirstName() . " " . $user->getLastName() . " --- \n" . $userAnswer->getUserAnswer());
} elseif (preg_match("/^\*.+/i", $userAnswer->getFormatUserAnswer())) {
	exit();
} elseif (preg_match("/^#.+/i", $userAnswer->getFormatUserAnswer())) {
	$respText = "Слова для викторины добавлены на рассмотрение. \nБольшое спасибо, " . $user->getFirstName() . "!";

	$messageSender = new MessageSender(ADMIN_ID, "<b>Вопросы к викторине</b> от пользователя " . $user->getFirstName() . " " . $user->getLastName() . "\n\n" . str_replace('#', '', $userAnswer->getUserAnswer()));
	$messageSender->sendMessage();

	$userWordsLog = new Log('/User_Words.log');
	$userWordsLog->log("--- " . $user->getFirstName() . " " . $user->getLastName() . " --- \n" . str_replace('#', '', $userAnswer->getUserAnswer()));
} elseif ($userAnswer->getFormatUserAnswer() === $game->getTrueQuestion()) {
	$respText = "Это мой вопрос, " . $user->getFirstName() . "!";
} elseif (preg_match("/([Пп]ока)|([Дд]о свидан)/ui", $userAnswer->getFormatUserAnswer())){
	$respText = "Γεια σας, " . $user->getFirstName() . "!";
} elseif (preg_match("/([Пп]ривет)|([Κκ]αλημέρα)|([Κκ]αλησπέρα)/ui", $userAnswer->getFormatUserAnswer())){
	$respText = "Γεια σας, " . $user->getFirstName() . "! Переведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
} elseif (preg_match("/game_change/i", $userAnswer->getFormatUserAnswer())){
	$game->changeGameMode();
	$game->startGame();
	$respText = "Игра началась!\n\nПереведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
} elseif (preg_match("/start_game/i", $userAnswer->getFormatUserAnswer())) {
	$game->startGame();
	$respText = "Игра началась!\n\nПереведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
} elseif (preg_match("/game_hint/i", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getFirstName() . " хочет подсказку!\n" . "Это слово произошло от «<b>" . $game->respArrNow()[0]["base"]. "</b>» — «<i>". $game->respArrNow()[0]["baseTransl"]. "</i>».";
} elseif (preg_match("/game_options/i", $userAnswer->getFormatUserAnswer())) {
	if (!$game->areOptionsDone()) {
		$fourOptionsArr = $game->selectFourOptions();
		for ($i = 0; $i < count($fourOptionsArr); $i++) { 
			$fourOptionsArr[$i] = mb_strtoupper(mb_substr($fourOptionsArr[$i], 0, 1)) . mb_substr($fourOptionsArr[$i], 1);
		}
		$respText = " Варианты ответа для " . $user->getFirstName() . "!\n" . "Слово «<b>" . $game->getTrueQuestion() . "</b>» означает: \n\n" . "<b>1. </b>" . ucfirst($fourOptionsArr[0]) . "\n" . "<b>2. </b>" . ucfirst($fourOptionsArr[1]) . "\n" . "<b>3. </b>" . ucfirst($fourOptionsArr[2]) . "\n" . "<b>4. </b>" . ucfirst($fourOptionsArr[3]);
	} else {
		$respText = "Варианты ответа уже были даны, " . $user->getFirstName() . "!\n" . "Переведите слово: «<b>" . $game->getTrueQuestion() . "</b>»";
	}
} elseif (preg_match("/game_dictionary/i", $userAnswer->getFormatUserAnswer()) || preg_match("/[Сс]ловарь/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $game->getDictionaryMessage();
} elseif (preg_match("/game_info/i", $userAnswer->getFormatUserAnswer())) {
	$respText = "<b>Информация об игре!</b>\n\nВы можете:\n— перезапустить игру, если хотите изменить вопрос, командой <b>/start_game</b>.\n— взять подсказку этимологии слова командой <b>/game_hint</b>.\n— взять 4 варианта ответа командой <b>/game_options</b>.\n— изменить режим игры (перевод слов с греческого на русский, или наоборот) командой <b>/game_change</b>.\n— взять помощь словаря командой <b>/game_dictionary</b> или словом <b>словарь</b> в чате.\n— узнать свой счёт в чате, своё место в рейтинге чата или топ игроков чата командами <b>Мой счёт</b>, <b>Мой рейтинг</b> и <b>Рейтинг чата</b>, соответственно.\n\n— если вы не хотите, чтобы бот вам отвечал, ставьте символ <b>*</b> в начало вашего сообщения в чате.\n— оставьте пожелание для развития игры или бота, написав с начала строки: <b>Пожелание</b>, за которым следует текст вашего пожелания.\n— добавьте <b>Свои Вопросы</b> для викторины, написав с начала строки символ: <b>#</b>, за которым укажите свои слова для викторины, желательно с переводом, в любом формате.\n\nВы можете добавить бота в свой чат, наделив его правами администратора, или общаться с ним напрямую.";
} elseif (preg_match("/[Оо]твет/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getFirstName() . " хочет ответ.\n" . "Но он его не получит! :)";
} elseif (preg_match("/[Нн]е знаю/ui", $userAnswer->getFormatUserAnswer()) || preg_match("/[Сс]даюсь/ui", $userAnswer->getFormatUserAnswer()) || preg_match("/[Δδ]εν ξέρω/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getFirstName() . ", подумайте ещё или смените вопрос командой «start_game».";
} elseif (preg_match("/[Мм]ой рейтинг/ui", $userAnswer->getFormatUserAnswer()) || preg_match("/[Уу] меня рейтинг/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getUserRatingMessage();
} elseif (preg_match("/[Мм]ой сч[её]т/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = "Ваш счёт = <b>" . $user->getUserScore() . "</b>";
} elseif (preg_match("/[Рр]ейтинг чата/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $room->getRoomRating($scoresFilePath);
} else {
	$respText = $userAnswer->wrongAnswerMessage($user->getFirstName(), $game->getTrueResponse()) . " " . $userAnswer->verbRestrictionMessage($game->getTrueResponse()) . $userAnswer->decideOnAnswerMessage() . "\n\nПереведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
}

//Sending the response to the API
$messageSender = new MessageSender($room->getID(), $respText);
try {
	$messageSender->sendMessage();
} catch (Exception $e) {
	$log->log('Exception! In the line: ' . __LINE__ . ', with text: ' . $e->getMessage());
}

/* Get the answer type */
function answerType($messageTypeArray) {

	if (array_key_exists('sticker', $messageTypeArray)) {
		return new StickerAnswer($messageTypeArray['sticker']);
	} elseif (array_key_exists('voice', $messageTypeArray)) {
		return new VoiceAnswer($messageTypeArray['voice']);
	} elseif (array_key_exists('photo', $messageTypeArray)) {
		return new PhotoAnswer($messageTypeArray['photo'][0]);
	} elseif (array_key_exists('left_chat_participant', $messageTypeArray)) {
		return new LeftParticipantAnswer($messageTypeArray['left_chat_participant']);
	} elseif (array_key_exists('new_chat_participant', $messageTypeArray)) {
		return new NewParticipantAnswer($messageTypeArray['new_chat_participant']);
	} elseif (array_key_exists('text', $messageTypeArray)) {
		return new TextAnswer($messageTypeArray['text']);
	} else {
		return new UndefinedAnswer();
	}
}