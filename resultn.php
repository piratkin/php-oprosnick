<?php

define("ACCEPT_IP", '127.0.0.1'); //адрес для которого разрешен доступ к ствтистике
define("PAGES",               5); //задаем длинну поля навигации  
define("OUTPUT_RECORDS",     25); //число записей на один лист
	
//Выкидываем ошибку 404 - файл не найден, если скрипт нащел поисковый робот
if(!empty($_SERVER['HTTP_USER_AGENT'])) {
    $userAgents = array("Google", "Slurp", "MSNBot", "ia_archiver", "Yandex", "Rambler");
    if(preg_match('/' . implode('|', $userAgents) . '/i', $_SERVER['HTTP_USER_AGENT'])) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
}

//отключаем логирование
@ini_set('error_log',NULL);
@ini_set('log_errors',0);
// Открываем БД (или создаем)
$db = new PDO('sqlite:' . __FILE__ . '.db');
//адрес клиента, реферальная ссылка, дата и время
$ip = $db->quote($_SERVER["REMOTE_ADDR"]); 
$ref = $db->quote($_SERVER['HTTP_REFERER']);
//$date = (new DateTime("NOW"))->format('Y-m-d H:i:s');
$date = date("Y-m-d H:i:s");

/*
 * Создаем таблицу БД
 */ 
$db->exec('CREATE TABLE IF NOT EXISTS poll (id INTEGER PRIMARY KEY AUTOINCREMENT, dat DATETIME, ip TEXT, vote TEXT, ref TEXT);');
 
 /*
  * функция устанавливающая куки
  */
function set_cookie($k, $v) {
    $_COOKIE[$k] = $v;
    setcookie($k, $v);
}

//детальная статистика по просмотрам страницы
if (isset($_GET['result'])) {
	
	$files  = 0; //список фаилов в базе
	$max_id = 0; //
	$counts = 0; //количество записей в базе
    $id     = is_int(intval($_GET['result'])) ? intval($_GET['result']) : 1; //адрес запрашиваемой страницы
	
	//получаем число строк в базе
	$counts = $db->query('SELECT COUNT(*) FROM poll;')->fetchColumn(); 
	
	if ($counts > 0) { 
		$max_id = intval(($counts - 1) / OUTPUT_RECORDS) + 1; 
		//проверка на привышение лимита страниц
		if ($id > $max_id) $id = $max_id; 
		if ($id < 1) $id = 1;
		//вычисляем начальное и конечное смещение окна навигации
		$cx = floor(PAGES/2);
		$start_id = $id - $cx;
		if (!(PAGES%2)) $start_id++;
		$end_id   = $id + $cx;
		//вжимаем окно в допустимые рамки
		while ($start_id < 1) {    //сначала двигаем "вправо",
			$start_id++; if ($end_id < $max_id) $end_id++;
		}
		while ($end_id > $max_id) {//а потом двигаем "влево"
			$end_id--; if ($start_id > 1) $start_id--;
		}
		
		$response = $db->prepare("SELECT * FROM poll ORDER BY id DESC LIMIT ".(($id*OUTPUT_RECORDS) - OUTPUT_RECORDS).", ".OUTPUT_RECORDS.";");
        $response->execute();
		
		//заголовок страницы 
		echo '<table><tr><th>№</th><th>Дата</th><th>Адрес</th><th>Голос</th><th>Реферальная ссылка</th></tr>';
		
		if ($response) {	
			//выводим список фаилов в хранилище
			while ($line = $response->fetch()) {
				$format = '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>';
				printf($format, $line["id"], date("d.m.Y", strtotime($line["dat"])), $line["ip"], $line["vote"], $line["ref"] );
			}
		    unset($response);
		}
		
		if ($start_id != $end_id) {
			echo '<tbody id="footer"><tr><td colspan="5" style="text-align: center;">Страницы: <ul class="pager">';
			if (($start_id - 1) > 1) echo '<li class="first"><a href="'.basename(__FILE__).'?result='.($start_id - 1).'">«</a></li>';   
			if ($id > 1) echo '<li class="previous"><a href="'.basename(__FILE__).'?result='.($id - 1).'">‹</a></li>';
			for ($iter = $start_id; $iter <= $end_id; $iter++) {
				echo '<li><a href="'.basename(__FILE__).'?result='.$iter.'">'.$iter.'</a></li>';
			}; 
			if ($id < $max_id) echo '<li class="next"><a href="'.basename(__FILE__).'?result='.($id + 1).'">›</a></li>';
			if (($end_id + 1) < $max_id) echo '<li class="last"><a href="'.basename(__FILE__).'?result='.($end_id + 1).'">»</a></li>';
			echo '</ul></td></tr></tbody>';
		}
		echo '</table>';
		echo '	<style>
					table { 
						background: #ccc;
						border: 1px solid #ccc;
						border-radius: 5px;
						width: auto;
						margin: auto;
						border-collapse: separate;
						border-spacing: 2px;
						display: table;
						text-indent: 0px;
						font: 13px "Trebuchet MS", "Trebuchet", "Verdana", sans-serif;
					}
					tr { 
						display: table-row;
						background: #fff;
						vertical-align: inherit;
					}
					th { 
						display: table-cell;
						font-weight: 700;
						vertical-align: inherit;
						background: #ccc;
						color: #666;
						//min-width: 100px;
					}
					table td { 
						max-width: 300px;
						padding: 2px 10px 4px;
						white-space: nowrap;
						overflow: hidden;
						-o-text-overflow:ellipsis;
						text-overflow: ellipsis;
					}
					td { 
						display: table-cell;
						vertical-align: inherit;
					}
					tbody { 
						display: table-row-group;
						vertical-align: middle;
						color: #666;
					}
					tbody tr:hover {
						background: #ddd;
						color: #000;
					} 
					#footer tr {
						color: #000;
					}
					#footer tr:hover {
						background: #fff;
					}
					a { 
						text-decoration: none;
						color: #38f;
					} 
					a:hover { 
						color: #f83;
					}
					ul.pager
					{
						font-size:12px;
						border:0;
						margin:0;
						padding:0;
						line-height:100%;
						display:inline;
					}
					ul.pager li
					{
						display:inline;
					}
					ul.pager a:link,
					ul.pager a:visited
					{
						border:solid 1px #ccc;
						font-weight:normal;
						color: #666;
						padding:1px 3px;
						text-decoration:none;
					}
					ul.pager .page a
					{
						font-weight:normal;
						margin: 0px 2px;
					}
					ul.pager a:hover
					{
						background-color: #eee;
						border:solid 1px #666;
						color: #666; 
					}
					ul.pager .selected a
					{
						background:#ddd;
						//color:#000;
						font-weight:bold;
					}
					ul.pager .hidden a
					{
						border:solid 1px #e1941f;
						color:#888888;
					}
					ul.pager .first,
					ul.pager .last
					{
						//display:none;
					}
				</style>'; 	
	} else {
		echo "<div align='center'>Записи в БД - отсутствуют!</div>";
	}
	if ($db) unset($db);
	exit;
}

//проверяем было ли голосование
if ($_SERVER["REMOTE_ADDR"] != ACCEPT_IP) {
	if(!isset($_COOKIE[md5($_SERVER['HTTP_HOST'] . vote)]) && isset($_GET['lei']) ) {
		//сохраняем результат голосования в куки
		set_cookie(md5($_SERVER['HTTP_HOST'] . vote), $_GET['lei']);
		//регистрируем голос в БД
		if ($_GET['lei'] == 'yes') {
			//голос - за
			$db->exec('INSERT INTO poll (ip, dat, vote, ref) VALUES ("'.$ip.'", "'.$date.'", "yes", "'.$ref.'");');
		} else {
			//голос - против
			$db->exec('INSERT INTO poll (ip, dat, vote, ref) VALUES ("'.$ip.'", "'.$date.'", "no", "'.$ref.'");');
		}
	} else {
		//регистрируем просмотр в БД
		$db->exec('INSERT INTO poll (ip, dat, ref) VALUES ("'.$ip.'", "'.$date.'", "'.$ref.'");');
	}
}

//загружаем статистику из БД
$result_yes = $db->query("SELECT COUNT(*) FROM poll WHERE vote='yes';")->fetchColumn(); 
$result_no = $db->query("SELECT COUNT(*) FROM poll WHERE vote='no';")->fetchColumn(); 

if ($result_yes == 0 && $result_no == 0) {
    $result_yes_prc = 0;
	$result_no_prc = 0;	
} else {
	$result_yes_prc = ($result_yes == 0) ? 0 : $result_yes/($result_yes + $result_no)*100;
	$result_no_prc = ($result_no == 0) ? 0 : $result_no/($result_yes + $result_no)*100;	
}

//пользователь уже голосовал
if (isset($_COOKIE[md5($_SERVER['HTTP_HOST'] . vote)]) || ($_SERVER["REMOTE_ADDR"] == ACCEPT_IP)) {
	echo   '<b style="margin: 0 0 8px 0; font-size: 14pt;">Результаты голосования:</b><br>
			<b>Да</b>
			<table style="width: 100%; border: 1px solid rgb(221, 221, 221);">
				<tr style="font-size: 10pt;">
					<td width=60%><div id="vote_yes_progress" style="width: '.round($result_yes_prc, 1).'%; background-color: Red; height: 16px;"></div></td>
					<td id="vote_yes_result"align="right"><b>('.$result_yes.')</b>'.round($result_yes_prc, 1).'%</td>
				</tr>
			</table>
			<b>Нет</b>
			<table style="width: 100%; border: 1px solid rgb(221, 221, 221);">
				<tr style="font-size: 10pt;">
					<td width=60%><div id="vote_no_progress" style="width: '.round($result_no_prc, 1).'%; background-color: Red; height: 16px;"></div></td>
					<td id="vote_no_result" align="right"><b>('.$result_no.')</b>'.round($result_no_prc, 1).'%</td>
				</tr>
			</table>
			<a href="'.basename(__FILE__).'?result" target="_blank"><button style="margin-top: 14px; width: 7em; height: 2.5em; font-size: 11pt; font-family: serif, sans-serif;" '.(($_SERVER["REMOTE_ADDR"] == ACCEPT_IP) ? '': 'disabled="disabled"').'>Подробнее</button></a>';
} else { //выводим опросник
	echo   '<form style="margin: 0;">
				<b style="margin-bottom: 8px; font-size: 14pt;">Заинтересована ли ваша организация в получении LEI-кода?</b><br>
				<input type="radio" name="lei" value="yes" style="margin: 10px 5px; padding: 8px;" checked /> Да</br>
				<input type="radio" name="lei" value="no" style="margin: 10px 5px; padding: 8px;" /> Нет</br>
				<a href="'.basename(__FILE__).'"><button style="width: 7em; height: 2.5em; margin-top: 8px; font-size: 11pt;font-family: serif, sans-serif;">Голосовать!</button></a>
			</form>';
}

if ($db) unset($db);
