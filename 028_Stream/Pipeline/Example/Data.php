<?
Http::$provider=mock_httpGetAsync(...);

class Http_Mock_Response
{
  private bool   $status  ;
  private string $content ;
  private string $error   ;
  
  static function answer ($content ) { return new static(true, $content); }
  static function error  ($error   ) { return new static(false, '', $error); }
  
  function __construct($status, $content, $error='')
  {
    $this->status  = $status  ;
    $this->content = $content ;
    $this->error   = $error   ;
  }
  
  function isSucessfull() { return $this->status; }
  function getContent() { return $this->content; }
  function getError() { return $this->error; }
};

/**
 * Мок для httpGetAsync (нужен для полной работы вашего пайплайна)
 */
function mock_httpGetAsync(string $url): Promise 
{
  // Парсим ID из урла, чтобы вернуть его в JSON, будто сервер реально ответил
  preg_match('/item_(\d+)/', $url, $matches);
  $id = (int)($matches[1] ?? rand(100, 999));

  $data=['id' => $id + 1000];
//if($id===41) $data=['wrong'=>$data];
  $content=json_encode($data);
  
//if($id===42) $content='Number of the universe';
  
  $response = Http_Mock_Response::answer($content); // ID в TableB будет +1000
  
  if($id===40) $response = Http_Mock_Response::error('Is the fortieth');

  $deferred = new Deferred();

  // Имитируем сетевую задержку в 50 мс
  Loop::delay(0.05, function() use ($deferred, $response) {
    if ($response->isSucessfull())
      $deferred->resolve($response->getContent());
    else
      $deferred->reject($response->getError());
  });

  return $deferred->promise();
}

(function(){
  // 1. Настраиваем TableA: 120 строк. База отдаст этот стрим с задержкой 0.1 сек.
  $tableAData =[];
  for ($i = 1; $i <= 120; $i++)
    $tableAData[] = ['id' => $i, 'name' => "Item_$i", 'url' => "https://api.com/item_$i"];
  
  MockDB::get()->setupResponse('SELECT * FROM TableA', delaySeconds: 0.1, data: $tableAData);
  
  // 2. Настраиваем TableB: ответы для первой пачки (ID 1001 - 1100) и второй (ID 1101 - 1120)
  // База будет <думать> над этим запросом 0.2 сек.
  $tableBData =[];
  for ($i = 1; $i <= 120; $i++)
    $tableBData[] = ['id' => $i+1000, 'amount' => 10]; // Каждая запись добавит 10 в сумму
  
  $tableBData[ 0]['amount'] = 1000; // Will skip
  $tableBData[11]['amount'] =    5; // Min:  5
  $tableBData[10]['amount'] =   15; // Max: 15
  
  function tableB_select($data, $sql, $params)
  {
    $ids = $params[0]?? null;
    if ($ids === null) return $data;
    $result = [];
    foreach ($data as $rec)
      if (in_array($rec['id'], $ids, true))
        $result[] = $rec;
  
    return $result;
  }
  
  MockDB::get()->setupResponse('SELECT * FROM TableB', delaySeconds: 0.2, data: $tableBData, callback: tableB_select(...));
  
  // 3. Настраиваем пустой ответ для Insert в TableC
  MockDB::get()->setupResponse('INSERT INTO TableC', delaySeconds: 0.05, data: []);
})();
