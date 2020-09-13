<?php

return [

    'It seems that you have not changed your access password for a long time. We recommend that you, periodically, change the password for access to the administrative interface of the site.' => "Похоже, что Вы давно не меняли пароль доступа. Рекомендуем переодически менять пароль доступа к административному интерфейсу сайта.",

    'Security' => "Безопасность",
    'Banned List' => "Список блокировок",

    'ID' => "ИД",
    'Client IP' => "IP клиента",
    'Client Net' => "Сеть клиента",
    'IP or/and Net' => "IP и/или сеть",
    'Сlient IP/Net' => "IP клиента/сеть",
    'IP range (start)' => "IP диапазон (начало)",
    'IP range (end)' => "IP диапазон (конец)",
    'User Agent' => "Польз. агент",
    'Status' => "Статус",
    'Reason' => "Причина",
    'Created' => "Создано",
    'Updated' => "Обновлено",
    'Created by' => "Автор создания",
    'Updated by' => "Автор изменений",
    'Release' => "Освобождение",
    'Actions' => "Действия",

    'All statuses' => "Все статусы",
    'Banned' => "Заблокирован",
    'Unbanned' => "Разблокирован",
    'Released' => "Освобождён",

    'Block by IP or network' => "Заблокировать по IP или сети",
    '{count} addresses added successfully!' => "{count} адрес(ов) успешно добавлены!",
    '{count} addresses were added successfully, but some errors occurred: {errors}' => "{count} адрес(ов) добавлено успешно, но возникли некоторые ошибки: {errors}",
    'An error occurred while add the addresses: {errors}' => "Ошибка при добавлении адресов: {errors}",

    'Test IP/Network' => "Тестировать IP/Cеть",
    'Test IP or network' => "Тестировать IP или сеть",

    'Ban' => "Заблокировать",
    'Unban' => "Разблокировать",
    'Release' => "Освободить",

    'Close' => "Закрыть",
    'Save' => "Сохранить",
    'Apply' => "Применить",
    'Go' => "Начать",

    'Blocking period' => "Срок блокировки",
    'Default' => "По-умолчанию",
    '1 hour' => "1 час",
    '6 hours' => "6 часов",
    '1 day' => "1 день",
    '1 week' => "1 неделя",
    '2 weeks' => "2 недели",
    '1 month' => "1 месяц",
    '6 months' => "6 месяцев",
    '1 year' => "1 год",
    'Lifetime' => "Пожизненно",

    'It looks like your IP matches the blocked `{ip}` and cannot be blocked.' => "Похоже, что Ваш IP совпадает с блокируемым `{ip}` и не может быть заблокирован.",
    'It looks like your IP belongs to the blocked `{subnet}` subnet and cannot be blocked.' => "Похоже, что Ваш IP входит в блокируемую подсеть `{subnet}` и не может быть заблокирован.",
    'It looks like your IP is in the blocking range `{start} - {end}` and cannot be blocked.' => "Похоже, что Ваш IP входит в блокируемый диапазон `{start} - {end}` и не может быть заблокирован.",

    'Specify a list of IP addresses or networks (each address or network - on a new line). The following options are allowed:' => "Укажите список IP адресов или сетей (каждый адрес или сеть - с новой строки). Разрешены такие варианты записи как:",
    'IPv4 address (for example: 172.104.89.12)' => "IPv4 адрес (например: 172.104.89.12)",
    'network address in the CIDR (for example: 172.104.89.12/24)' => "адрес сети в виде CIDR (например: 172.104.89.12/24)",
    'network address with mask 172.104.89.0/255.255.255.0' => "адрес сети с маской 172.104.89.0/255.255.255.0",
    'address range like 172.104.89.0-172.104.89.255' => "диапазон адресов в виде 172.104.89.0-172.104.89.255",
    'IPv6 address or network (2002::ac68:590c, 2002::ac68:5900/120)' => "IPv6 адрес или сеть (2002::ac68:590c, 2002::ac68:5900/120)",

    'All reasons' => "Все причины",
    'Manual blocking' => "Ручная блокировка",
    'Rate limit' => "Превышение лимита",
    'Overdrive attack' => "Атака переполнения",
    'XSS-attack' => "XSS-атака",
    'LFI/RFI/RCE attack' => "LFI/RFI/RCE атака",
    'PHP-injection' => "PHP-инъекция",
    'SQL-injection' => "SQL-инъекция",

    'First page' => 'Первая страница',
    'Last page' => 'Последняя страница',
    '&larr; Prev page' => '&larr; Предыдущая страница',
    'Next page &rarr;' => 'Следующая страница &rarr;',

    'Add/update' => "Добавить/редактировать",

    'Rate limit exceeded.' => "Превышение лимита запросов.",
    'Overdrive attack detected.' => "Обнаружена атака овердрайва.",
    'XSS-attack detected.' => "Обнаружена XSS-атака.",
    'LFI/RFI/RCE attack detected.' => "Обнаружена LFI/RFI/RCE атака.",
    'PHP-injection detected.' => "Обнаружена попытка PHP-инъекции.",
    'SQL-injection detected.' => "Обнаружена попытка SQL-инъекции.",
    'Access denied from security reason.' => "В доступе отказано по соображениям безопасности.",

];

?>
