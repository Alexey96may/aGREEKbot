<?php
require_once 'app/classes/User.php';
require_once 'app/classes/Room.php';
require_once 'app/classes/Game.php';
require_once 'app/classes/core/logs/Log.php';
require_once 'app/classes/AnswerForms/UndefinedAnswer.php';
require_once 'app/classes/AnswerForms/TextAnswer.php';
require_once 'app/classes/AnswerForms/VoiceAnswer.php';
require_once 'app/classes/AnswerForms/PhotoAnswer.php';
require_once 'app/classes/AnswerForms/StickerAnswer.php';
require_once 'app/classes/AnswerForms/LeftParticipantAnswer.php';
require_once 'app/classes/AnswerForms/NewParticipantAnswer.php';

use App\Classes\User;
use App\Classes\Room;
use App\Classes\Game;
use App\Classes\Core\Logs\Log;
use App\Classes\AnswerForms\UndefinedAnswer;
use App\Classes\AnswerForms\TextAnswer;
use App\Classes\AnswerForms\VoiceAnswer;
use App\Classes\AnswerForms\PhotoAnswer;
use App\Classes\AnswerForms\StickerAnswer;
use App\Classes\AnswerForms\LeftParticipantAnswer;
use App\Classes\AnswerForms\NewParticipantAnswer;

//Class instances creating
if (class_exists('App\Classes\Core\Logs\Log')) {
	Log::setRootLogDir('./logs');
	$log = new Log('/tgBot.log');
} else {
	die('Class existing Error!');
}

//Token
$secretToken = file_get_contents(__DIR__."/init/token.txt");
define("BOTTOKEN", $secretToken);

//Template file prefix
define("TEMPL_PREFIX", __DIR__."/temp");

//Main data
$data = file_get_contents('php://input');
$dataArray = json_decode($data, true);

$messageType = 'message';
if (array_key_exists('edited_message', $dataArray) ) {
	$messageType = 'edited_message';
}

$userAnswer = new UndefinedAnswer();

if (array_key_exists('sticker', $dataArray[$messageType])) {
	$userAnswer = new StickerAnswer($dataArray[$messageType]['sticker']);
}
if (array_key_exists('voice', $dataArray[$messageType])) {
	$userAnswer = new VoiceAnswer($dataArray[$messageType]['voice']);
}
if (array_key_exists('photo', $dataArray[$messageType])) {
	$userAnswer = new PhotoAnswer($dataArray[$messageType]['photo'][0]);
}
if (array_key_exists('left_chat_participant', $dataArray[$messageType])) {
	$userAnswer = new LeftParticipantAnswer($dataArray[$messageType]['left_chat_participant']);
}
if (array_key_exists('new_chat_participant', $dataArray[$messageType])) {
	$userAnswer = new NewParticipantAnswer($dataArray[$messageType]['new_chat_participant']);
}
if (array_key_exists('text', $dataArray[$messageType])) {
	$userAnswer = new TextAnswer($dataArray[$messageType]['text']);
}

//Class instances creating
if (class_exists('App\Classes\User') && class_exists('App\Classes\Room')) {
	$user = new User($dataArray[$messageType]['from']);
	$room = new Room($dataArray[$messageType]['chat']);
} else {
	$log->log('Class existing Error! In the line' . __LINE__);
	die('Class existing Error!');
}

//Complete file Paths according to this chat
$settingsFilePath = TEMPL_PREFIX.'/settings_' . $room->roomIDPath() . '.txt';
$scoresFilePath = TEMPL_PREFIX.'/scores_' . $room->roomIDPath() . '.txt';
$translTrain_copyFilePath = TEMPL_PREFIX.'/translTraining_copy' . $room->roomIDPath() . '.txt';
$userMessageFilePath = TEMPL_PREFIX.'/message.txt';
$logFilePath = TEMPL_PREFIX.'/log.txt';
$errorsFilePath = TEMPL_PREFIX.'/errors.txt';

//Setting and Score files forming
if (!file_exists($settingsFilePath)) {
	file_put_contents($settingsFilePath, '[]', LOCK_EX);
}
if (!file_exists($scoresFilePath)) {
	file_put_contents($scoresFilePath, '[]', LOCK_EX);
}
if (!file_exists($translTrain_copyFilePath)) {
	file_put_contents($translTrain_copyFilePath, '[]', LOCK_EX);
}

//Class instances creating
if (class_exists('App\Classes\Game')) {
	$game = new Game($settingsFilePath, $translTrain_copyFilePath);
} else {
	$log->log('Class Game existing Error! In the line' . __LINE__);
	die('Class existing Error!');
}

if ($userAnswer->getAnswerType() === 'undefined'){
	$respText = "Просьба отвечать текстом, " . $user->getFirstName() . ".";
} elseif ($userAnswer->getAnswerType() === 'voice') {
	$respText = "У вас прекрасный голос, " . $user->getFirstName() . "! Но я жду текстовый ответ.";
} elseif ($userAnswer->getAnswerType() === 'sticker') {
	$respText = $userAnswer->getEmoji();
} elseif ($userAnswer->getAnswerType() === 'photo') {
	$respText = "Надеюсь, на фото ответ, " . $user->getFirstName() . ". Но я понимаю только текст!";
} elseif ($userAnswer->getAnswerType() === "new_chat_participant"){
	$respText = "Γεια σας, " . $userAnswer->getFirstName() . "! Переведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
} elseif ($userAnswer->getAnswerType() === "left_chat_participant"){
	$respText = "Αντίο, " . $userAnswer->getFirstName() . ". Στο καλό!";
} elseif (preg_match("/^[Пп]ожелание.+/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = "Пожелание принято. Спасибо, " . $user->getFirstName() . "!";
	$desireLog = new Log('/userDesires/tgBot.log');
	$desireLog->log('--- ' . $user->getFirstName() . ' ' . $user->getLastName() . ' --- ' . $userAnswer->getUserAnswer());
} elseif (preg_match("/^\*.+/i", $userAnswer->getFormatUserAnswer())) {
	exit();
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
} elseif (preg_match("/game_dictionary/i", $userAnswer->getFormatUserAnswer()) || preg_match("/[Сс]ловарь/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $game->getDictionaryMessage();
} elseif (preg_match("/game_info/i", $userAnswer->getFormatUserAnswer())) {
	$respText = "<b>Информация об игре!</b>\n\nВы можете:\n— перезапустить игру, если хотите изменить вопрос, командой «<b>start_game</b>».\n— взять подсказку командой «<b>game_hint</b>».\n— изменить режим игры (перевод слов с греческого на русский, или наоборот) командой «<b>game_change</b>».\n— взять помощь словаря командой «<b>game_dictionary</b>» или словом «<b>словарь</b>» в чате.\n— узнать свой счёт в чате, своё место в рейтинге чата или топ игроков чата командами «Мой счёт», «Мой рейтинг» и «Рейтинг чата», соответственно.\n\n— если вы не хотите, чтобы бот вам отвечал, ставьте символ «<b>*</b>» в начало вашего сообщения в чате.\n— оставьте пожелание для развития игры или бота, написав с начала строки: «<b>Пожелание</b>», за которым следует текст вашего пожелания.\n\nВы можете добавить бота в свой чат, наделив его правами администратора.";
} elseif (preg_match("/[Оо]твет/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getFirstName() . " хочет ответ.\n" . "Но он его не получит! :)";
} elseif (preg_match("/[Нн]е знаю/ui", $userAnswer->getFormatUserAnswer()) || preg_match("/[Сс]даюсь/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getFirstName() . ", подумайте ещё или смените вопрос командой «start_game».";
} elseif (preg_match("/[Мм]ой рейтинг/ui", $userAnswer->getFormatUserAnswer()) || preg_match("/[Уу] меня рейтинг/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $user->getUserRatingMessage($scoresFilePath);
} elseif (preg_match("/[Мм]ой сч[её]т/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = "Ваш счёт = <b>" . $user->getUserScore($scoresFilePath) . "</b>";
} elseif (preg_match("/[Рр]ейтинг чата/ui", $userAnswer->getFormatUserAnswer())) {
	$respText = $room->getRoomRating($scoresFilePath);
} elseif ($userAnswer->isCorrectAnswer($game->getTrueResponse())) {
	$game->userWin();
	$user->setUserScore($scoresFilePath, $userAnswer->getAnswerPrice());
	$respText = "Правильно, " . $user->getFirstName() . "! Ответ был: «{$game->getTrueResponse()}». \nВаш счёт = <b>" . $user->getUserScore($scoresFilePath) . "</b>.\n\nПереведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
} else {
	$respText = $userAnswer->wrongAnswerMessage($user->getFirstName(), $game->getTrueResponse()) . " " . $userAnswer->verbRestrictionMessage($game->getTrueResponse()) . $userAnswer->decideOnAnswerMessage() . "\n\nПереведите слово: " . " «<b>" . $game->getTrueQuestion() . "</b>».";
}

$getQuery = array(
	'chat_id' 		=> $room->getID(),
	'text'			=> $respText,
	'parse_mode'	=> "HTML",
);

try {
    TG_sendMessage($getQuery);
} catch (Exception $e) {
	$log->log('Exception! In the line' . __LINE__);
}

//Functions

/* Message Sender */
function TG_sendMessage($getQuery) {
    $ch = curl_init("https://api.telegram.org/bot". BOTTOKEN ."/sendMessage?" . http_build_query($getQuery));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $res = curl_exec($ch);
    curl_close($ch);

    return $res;
}