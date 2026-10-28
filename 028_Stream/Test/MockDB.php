<?php
/**
 * Мок-База данных с возможностью задавать задержки ответов
 */
class MockDB
{
  static self $instance;
  
  static function get() { return self::$instance??=new MockDB(); }

  private array $delayedResponses =[];

  /**
   * Позволяет настроить: при совпадении подстроки в SQL, через сколько секунд
   * и какие данные нужно вернуть.
   */
  function setupResponse(string $sqlPattern, float $delaySeconds, array $data, ?callable $callback=null): void
  {
    $this->delayedResponses[] = [
      'pattern'  => $sqlPattern   ,
      'delay'    => $delaySeconds ,
      'data'     => $data         ,
      'callback' => $callback     ,
    ];
  }

  /**
   * Имитация обычного queryAsync, возвращающего весь результат
   */
  function queryAsync(string $sql, array $params =[]): Promise
  {
    $deferred = new Deferred();
    $match = $this->internalQuery($sql, $params);

    Loop::delay($match['delay'], function() use ($deferred, $match) {
      $deferred->resolve($match['result']);
    });

    return $deferred->promise();
  }

  /**
   * Имитация queryAsyncStream, возвращающего PHP-генератор строк
   */
  function queryAsyncStream(string $sql, array $params =[]): \Generator
  {
    $match = $this->internalQuery($sql, $params);

    // Имитируем, что база думала перед тем, как отдать первую строку
    sleepAsync($match['delay'])->await();

    foreach ($match['result'] as $row)
    {
      // Имитируем микро-задержку выдачи каждой строки из курсора
      sleepAsync(0.002)->await();
      yield $row;
    }
  }
  
  private function internalQuery(string $sql, array $params =[])
  {
    $match = $this->findMatch($sql);
    $result = $match['data'];
    if ($callback = $match['callback'])
      $result = $callback($result, $sql, $params);
    $match['result'] = $result;
    return $match;
  }

  private function findMatch(string $sql): array
  {
    foreach ($this->delayedResponses as $config)
      if (str_contains($sql, $config['pattern']))
        return $config;
        
    // Дефолтный быстрый пустой ответ, если ничего не настроено
    return ['delay' => 0.0, 'data' => []];
  }

  Function _Debug_Serialize(Array &$Res)
  {
    UnSet($Res['delayedResponses']);
  }
}
