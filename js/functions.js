//ИНН текущей компании
var inn = '';
//дата сохранения информации о заблок.счетах на текущей открытой странице 
var date = '';
//номер текущей открытой страница с заблок.счетами
var page = 1;

$(document).ready(function(){
	inn = '';
	save_date = '';
	page = 1;

	//получениее ИНН компании из адресной строки
	var paramsStr = decodeURIComponent(location.search.substr(1)).split('&');
	paramsStr.forEach(function(str){
		if (str.startsWith('inn=')) 
		{
			if (str.substr(4).search(/^\d{10}$/) == -1)
				$(location).attr('href','/');
			inn = str.substr(4);

			//отправка запроса на информацию о компании по ИНН
			$.ajax({
				url: "/php/findCompany.php",
				type: "POST",
				data: {inn: inn},
				success: function(data){
					UpdateInfo(data);
				}
			});
		}
	});
});

//при отправке ИНН из формы
$("form").submit(function(e){
	e.preventDefault();
	//проверка ИНН на корректность и добавление его в адресную строку
	if ($('#inn').val().search(/^\d{10}$/) !== -1)
		$(location).attr('href','/?inn='+$('#inn').val());
});

//при клике на кнпку Обновить (заблок счета)
$(document).on('click','#update', function(e){
	//отправка запроса на обновление данных
	$.ajax({
		url: "/php/updateBlockedAccs.php",
		type: "POST",
		data: {inn: inn},
		success: function(data){
			UpdateInfo(data);
		}
	});
});

//при клике на другую страницу
$(document).on('click','.page', function(e){
	page = $(this).text();
	$.ajax({
		url: "/php/findCompany.php",
		type: "POST",
		data: {inn: inn, page: page, save_date: save_date},
		success: function(data){
			try{
				var company = $.parseJSON(data);
				$("#blocked-accs").empty();
				ShowBlockedAccs(company);
			}
			catch(e){
				console.log(data);
			}
		}
	});
});

//при клике на другую дату сохранения заблок.счетов
$(document).on('click','.date', function(e){
	save_date = $(this).text();
	page = 1;
	$.ajax({
		url: "/php/findCompany.php",
		type: "POST",
		data: {inn: inn, page: 1, save_date: save_date},
		success: function(data){
			try{
				var company = $.parseJSON(data);
				$("#blocked-accs").empty();
				ShowBlockedAccs(company);
			}
			catch(e){
				console.log(data);
			}
		}
	});
});

/**
* функция обновления информации о компании на странице
* @param string data - информация о компании в строке json
*/
function UpdateInfo(data)
{
	$("#company-info").empty();
	$("#blocked-accs").empty();
	
	try{
		var company = $.parseJSON(data);
		page = 1;
		save_date = '';
		ShowMainInfo(company);
		ShowBlockedAccs(company);
	}
	catch(e){
		alert(data);
	}
}

/**
* функция отображения основной информации о компании
* @param string company - информация о компании в виде объекта
*/
function ShowMainInfo(company)
{
	$("#company-info")
		.append("<span>Наименование: "+company.name+"</span><br>")
		.append("<span>Инн: </span><span id='inn-text'>"+company.inn+" </span><br>")
		.append("<span>Статус: "+company.status+"</span><br>");

	var dirs = company.directors;
	if(dirs.length > 0)
	{
		dirs.forEach(function(dir){
			$("#company-info").append("<span>"+dir.job+": "+dir.name+"</span><br>");
		});
	}
}

/**
* функция отображения информации о з.счетах компании
* @param string company - информация о компании в виде объекта
*/
function ShowBlockedAccs(company)
{
	$("#blocked-accs").append("<span>Всего заблокированных рассчетных счетов: "+company.blockedAccsCount+"</span><br>");

	var accs = company.blockedAccs;
	if(accs.length > 0)
	{
		if (save_date == '') save_date = accs[0].save_date;

		CreatePageButtons(company.blockedAccsCount);
		$("#blocked-accs").append("<br>");
		CreateDateButtons(company.save_dates);
		$("#blocked-accs").append("<br>")
						  .append("<button id='update'>Обновить</button>");

		accs.forEach(function(acc){
			$("#blocked-accs").append("<hr><div class='acc'>"+
				"<span>Номер решения о приостановлении: "+acc.number+"</span><br>"+
				"<span>Дата решения о приостановлении: "+acc.date+"</span><br>"+
				"<span>Код налогового органа: "+acc.code+"</span><br>"+
				"<span>БИК / к.сч банка, в который направлено решение: "+acc.bic+"</span><br>"+
				"<span>Информация о банке: "+acc.bank_info+"</span><br>"+
				"<span>Дата и время размещения информации (Мск): "+acc.updated+"</span><br>"+
				"</div>");
		});
	}
}

/**
* функция создания кнопок с номерами страниц
* @param int accsCount - количество заблокированных счетов на заданную дату сохранения их в базе
*/
function CreatePageButtons(accsCount)
{
	for (var i = 1; i <= Math.ceil(accsCount/20); i++) {
		if (i == page) 
			$("#blocked-accs").append("<button class='page active' id='page"+i+"'>"+i+"</button>");
		else
			$("#blocked-accs").append("<button class='page' id='page"+i+"'>"+i+"</button>");
	}
}

/**
* функция создания кнопок с датами сохранения счетов в базе
* @param array dates - массив с датами
*/
function CreateDateButtons(dates)
{
	if(dates.length > 0) 
	{
		dates.forEach(function(date){
			if (date == save_date)
				$("#blocked-accs").append("<button class='date active'>"+date+"</button>");
			else
				$("#blocked-accs").append("<button class='date'>"+date+"</button>");
		});
	}
}