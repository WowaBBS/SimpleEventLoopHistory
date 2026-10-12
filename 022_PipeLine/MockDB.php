<?
/**
 * Мок-База данных с возможностью задавать задержки ответов
 */
class MockDB
{
  private array $delayedResponses =[];

  /**
   * Позволяет настроить: при совпадении подстроки в SQL, через сколько секунд
   * и какие данные нужно вернуть.
   */
  function setupResponse(string $sqlPattern, float $delaySeconds, array $data): void
  {
    $this->delayedResponses[] = [
      'pattern' => $sqlPattern,
      'delay' => $delaySeconds,
      'data' => $data
    ];
  }

  /**
   * Имитация обычного queryAsync, возвращающего весь результат
   */
  function queryAsync(string $sql, array $params =[]): Promise
  {
    $deferred = new Deferred();
    $match = $this->findMatch($sql);

    Loop::delay($match['delay'], function() use ($deferred, $match) {
      $deferred->resolve($match['data']);
    });

    return $deferred->promise();
  }

  /**
   * Имитация queryAsyncStream, возвращающего PHP-генератор строк
   */
  function queryAsyncStream(string $sql, array $params =[]): \Generator
  {
    $match = $this->findMatch($sql);

    // Имитируем, что база думала перед тем, как отдать первую строку
    sleepAsync($match['delay'])->await();

    foreach ($match['data'] as $row)
    {
      // Имитируем микро-задержку выдачи каждой строки из курсора
      sleepAsync(0.002)->await();
      yield $row;
    }
  }

  private function findMatch(string $sql): array
  {
    foreach ($this->delayedResponses as $config)
      if (str_contains($sql, $config['pattern']))
        return $config;
        
    // Дефолтный быстрый пустой ответ, если ничего не настроено
    return ['delay' => 0.0, 'data' => []];
  }
}
