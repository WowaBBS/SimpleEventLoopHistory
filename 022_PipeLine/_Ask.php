
//******************************************
Как мне сделать удобное асинхронное чтение таблицы TableA
Каждую строку асинхронно записывать в лог, так же запрашивать данные по ХТТП запросу
по каждой строке.
Результат группировать по 100 ответов, если за секунду 100 не набралось, то делать следующее:
Запрашивать для группы информацию из таблицы TableB
Для каждой записи записывать асинхронно в лог
Подсчитывть сумму определённого поля в последней записи
И по завершению записывать результат в таблицу TableC

//******************************************

Что насчёт или что-то наподобие:
$task='MyTask';
$db->query('SELECT * FROM TableA WHERE task=\''.$task,'\'')
  ->forEach(function($row, $isEnd){
    Log::Async('Get TableA.Item.Name ', $Row->name);
    return [$row, httpGetAsync($row->url)];
  })
  ->group(count: 100, timeout: 1.0)
  ->forEach(function($list, $isEnd) use($db){
    Log::Async("Get received ",count($list), ' ansvers');
    $ids=[];
    foreach($list As [$row, $answer]){
      if(!$answer->isSucessfull()) {
        Log::Async('Wrong answer: ',$answer->getErrorCode(), ' ', $answer->getErrorInfo());
        continue;
      }
      $data=json_decode($response->getContent());
      $ids[]=$data->id;
    }
    $result=$db->query('SELECT * FROM TableB WHERE id in('.Join(',', $Ids).')');
    return $result;
  })
  ->sum(fn($row)=>$row->amount);
  ->forEach(function ($totalAmount){
    Return $db->query('INSERT INTO TableC (task, totalAmount, time) VALUES(\''.$task.'\', '.$totalAmount.', NOW())');
  })
  ->finish();
