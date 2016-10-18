# MailBox

Функционал для работы с почтовым сервером, поддержка imap (pop2, smtp в планах)

Использует eden/mail

Как использовать:
```
//подключаем источник
$MailSource = new \Mailbox\MailSource('imap.gmail.com', '***@autospares.lv',"****",993);

//вывод каталогов ящика
$MailBoxes = $MailSource->getMailBoxes();

//Получние конкретного каталога по умолчанию INBOX
$MailBox = $MailSource->getMailBox("Прайсы");

//получение кол-ва писем
$count = $MailBox->getCount();

//получение всех писем, по умолчнаию работает лимит 1000
$Mails = $MailBox->getMails();

//Поиск по криетриям
$Criteria = (new \Mailbox\SearchCriteria)
		->setFrom("artis.pesla@inchcape.lv")//от
		->setSubject("прайс")//тем содержит
		->setSince("2016-10-15")//не старше даты
		->setAttachment('m_price');//поиск с именем вложения

//поиск первого совпадающего по критериям письма, по умолчанию ищет по 1000 последним письмам
$Mail = $MailBox->getMailByCriteria($Criteria);

//поиск первого совпадающего по критериям письма,
$Mail = $MailBox->getMailByCriteria($Criteria,true);

//тоже самое но найдет все письма с совпадающим криетриями
$Mails = $MailBox->getMailsByCriteria($Criteria);
```