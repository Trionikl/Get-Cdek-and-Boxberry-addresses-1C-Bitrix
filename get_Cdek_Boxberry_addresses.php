<?
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
	
	// Очистить таблицу(truncate table)	
	$connection = \Bitrix\Main\Application::getConnection();
	$connection->truncateTable('nen_delivery_address');
	
	//boxberry -----------------------------------------------------------------------
	$delivery_service = "Boxberry";
	
	$arStreetsLanes = array('пр-кт', ' ш', 'пер', 'дор', 'наб', 'б-р', 'ул');
		
	//Код города
	// "CityCode"
	// Нижний Тагил 34 тут адрес сайта
	// Уфа 60 тут адрес сайта
	// Тюмень 67 тут адрес сайта
	// Санкт-Петербург 116 тут адрес сайта
	// Пермь 41
	// Москва 68
	// Екатеринбург 16
	// Челябинск 63
	
	$arCityCodeDomainBoxberry = array( 
	array("60", "тут адрес сайта"),
	array("67", "тут адрес сайта"),
	array("116", "тут адрес сайта"),
	array("41", "тут адрес сайта"),
	array("68", "тут адрес сайта"),
	array("16", "тут адрес сайта"),
	array("63", "тут адрес сайта")
	); 
	
	foreach($arCityCodeDomainBoxberry as $key=>$value)
	{ 
		add_Table_Nen_delivery_address($arCityCodeDomainBoxberry[$key][0], $arCityCodeDomainBoxberry[$key][1], $delivery_service, $arStreetsLanes);	
		
		//	echo $key . PHP_EOL;	
		// ожидание в течениe 1 секунд
		//	sleep(1);
	}
	
	// СДЭК ----------------------------------------------------------
	//https://confluence.cdek.ru/pages/viewpage.action?pageId=15616129
	//Код города
	// "CityCode"
	// Нижний Тагил 251 тут адрес сайта
	// Уфа 256 тут адрес сайта
	// Тюмень 252 тут адрес сайта
	// Санкт-Петербург 137 тут адрес сайта
	// Пермь 248
	// Москва 44
	// Екатеринбург 250
	// Челябинск 259
	
	$arCityCodeDomainBoxberry = array( 
	array("256", "тут адрес сайта"),
	array("252", "тут адрес сайта"),
	array("137", "тут адрес сайта"),
	array("248", "тут адрес сайта"),
	array("44", "тут адрес сайта"),
	array("250", "тут адрес сайта"),
	array("259", "тут адрес сайта")
	); 	
	
	$delivery_service = "cdek";
	
	foreach($arCityCodeDomainBoxberry as $key=>$value)
	{ 
		add_Table_Nen_delivery_address($arCityCodeDomainBoxberry[$key][0], $arCityCodeDomainBoxberry[$key][1], $delivery_service, $arStreetsLanes);			
		
		// ожидание в течениe 1 секунд
		//	sleep(1);
	}	
	
	echo "Запись в таблицу завершена";
	
	
	//ФУНКЦИИ	-----------------------------------------------------------------------
	function add_Table_Nen_delivery_address($CityCode, $domain, &$delivery_service, &$streetsLanes)
	{
		
		//Подготовка
		if (CModule::IncludeModule('highloadblock')) {
			$arHLBlock = Bitrix\Highloadblock\HighloadBlockTable::getById(9)->fetch();
			$obEntity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arHLBlock);
			$strEntityDataClass = $obEntity->getDataClass();
		}			
		
		//Boxberry
		if  ($delivery_service == "Boxberry") {
			//https://documenter.getpostman.com/view/7354859/SzezaWVs#190f1627-c943-4b26-a3ac-58db8e7355e0
			//https://help.boxberry.ru/pages/viewpage.action?pageId=1703985	
			
			// Лимиты на запросы к API
			// Стандартный лимит	59 rps
			// PointsDescription	15 rps
			
			// 1. инициализация
			$ch = curl_init();	
			// 2. указываем параметры, включая url
			curl_setopt($ch, CURLOPT_URL, "https://api.boxberry.ru/json.php?token=2d9791dfcc0000000000a8f29507efa44&method=ListPoints&prepaid=1&CountryCode=643&CityCode=" . $CityCode);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);	
			// 3. получаем HTML в качестве результата
			$output = curl_exec($ch);	
			// 4. закрываем соединение
			curl_close($ch);	
			
			$json_answer = json_decode($output, true);	
			
		}
		
		//СДЕК
		if  ($delivery_service == "cdek") {		
			$sn=0;
			$xml = simplexml_load_file('https://integration.cdek.ru/pvzlist/v1/xml?cityid=' . $CityCode);
			foreach($xml as $key=>$value)
			{
				
				$json_answer[$sn][Address] = "г. " . $xml->Pvz[$sn][City] . ", " . $xml->Pvz[$sn][Address];
				$json_answer[$sn][CityCode] = $xml->Pvz[$sn][CityCode];
				$json_answer[$sn][Phone] = $xml->Pvz[$sn][Phone];
				$json_answer[$sn][WorkShedule] = $xml->Pvz[$sn][WorkTime];
				
				$sn++;
			}
			
		}
		
		foreach($json_answer as $key=>$value)
		{ 
			//echo $key . PHP_EOL;				
			if ($delivery_service=="Boxberry") {
				$json_answer[$key][Address] = address_Remake($json_answer[$key][Address], $streetsLanes); 
				}			
			
			//Добавление
			if (CModule::IncludeModule('highloadblock')) {
				$arElementFields = array(
				'UF_ADDRESS' => $json_answer[$key][Address],
				'UF_EXTERNAL_CODE' => $json_answer[$key][CityCode],
				'UF_TELEPHONE' => $json_answer[$key][Phone],
				'UF_OPERATING_MODE' => $json_answer[$key][WorkShedule],
				'UF_DOMAIN' => $domain,
				'UF_DELIVERY_SERVICE' => $delivery_service
				);
				$obResult = $strEntityDataClass::add($arElementFields);
				$ID = $obResult->getID();
				// echo $ID;
				$bSuccess = $obResult->isSuccess();
			}
			else
			break;		
		}
	}	
	

//переделать адрес у Boxberry 
function address_Remake($addressBoxberry, &$streetsLanes) {	
$arAddressBoxberry = explode(",", $addressBoxberry);

 foreach ($streetsLanes as $key => $value) {
	
 if ( preg_match('/' . $streetsLanes[$key] . '/i', $arAddressBoxberry[2]) ) {
	 $arAddressBoxberry[2] = str_replace($streetsLanes[$key], "", $arAddressBoxberry[2]);
	 $arAddressBoxberry[2] = trim($arAddressBoxberry[2]);
	 $arAddressBoxberry[2] = $streetsLanes[$key] . ". " . $arAddressBoxberry[2];
	
 } 
	
 }

$arAddressBoxberry[1] = str_replace("г", "", $arAddressBoxberry[1]);
$arAddressBoxberry[1] = trim($arAddressBoxberry[1]);
$arAddressBoxberry[1] = "г. " . $arAddressBoxberry[1];

$arAddressBoxberry[3] = str_replace("д.", "", $arAddressBoxberry[3]);
$arAddressBoxberry[3] = trim($arAddressBoxberry[3]);

//echo "<pre>"; print_r($arAddressBoxberry); echo "</pre>";
$addressBoxberry = $arAddressBoxberry[1] . ", " . $arAddressBoxberry[2] . ", " . $arAddressBoxberry[3] . " " . $arAddressBoxberry[4] . $arAddressBoxberry[5];

return $addressBoxberry;
}	
	
?>