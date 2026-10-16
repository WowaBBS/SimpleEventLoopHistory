<?
$httpGetAsync_Provider=mock_httpGetAsync(...);
/**
 * Мок для httpGetAsync (нужен для полной работы вашего пайплайна)
 */
function mock_httpGetAsync(string $url): Promise 
{
  $deferred = new Deferred();

  // Имитируем сетевую задержку в 50 мс
  Loop::delay(0.05, function() use ($deferred, $url) {
    // Парсим ID из урла, чтобы вернуть его в JSON, будто сервер реально ответил
    preg_match('/item_(\d+)/', $url, $matches);
    $id = $matches[1] ?? rand(100, 999);

    $mockResponse = new class($id) {
      private int $id;
      public function __construct($id) { $this->id = $id; }
      public function isSucessfull() { return true; }
      public function getContent() { return json_encode(['id' => $this->id + 1000]); } // ID в TableB будет +1000
    };

    $deferred->resolve($mockResponse);
  });

  return $deferred->promise();
}

$db = new MockDB();

// 1. Настраиваем TableA: 120 строк. База отдаст этот стрим с задержкой 0.1 сек.
$tableAData =[];
for ($i = 1; $i <= 120; $i++) {
  $tableAData[] = ['id' => $i, 'name' => "Item_$i", 'url' => "https://api.com"];
}
$db->setupResponse("SELECT * FROM TableA", delaySeconds: 0.1, data: $tableAData);

// 2. Настраиваем TableB: ответы для первой пачки (ID 1001 - 1100) и второй (ID 1101 - 1120)
// База будет <думать> над этим запросом 0.2 сек.
$tableBData =[];
for ($i = 1; $i <= 120; $i++) {
  $tableBData[] = ['id' => $i + 1000, 'a_id' => $i, 'amount' => 10]; // Каждая запись добавит 10 в сумму
}
$db->setupResponse("SELECT * FROM TableB", delaySeconds: 0.2, data: $tableBData);

// 3. Настраиваем пустой ответ для Insert в TableC
$db->setupResponse("INSERT INTO TableC", delaySeconds: 0.05, data: []);
