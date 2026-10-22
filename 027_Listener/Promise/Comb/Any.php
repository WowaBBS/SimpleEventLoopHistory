<?
/**
 * Возвращает промис, который выполнится успешно, как только выполнится успешно
 * хотя бы один из промисов в массиве.
 * Если все промисы отклонены, возвращает ошибку AggregateException.
 */
class Promise_Comb_Any extends Promise_Comb_Base
{
  function process(): self
  {
    foreach ($this->list as &$item)
    {
      if (!$item instanceof Promise)
      {
        // Обычное значение считается мгновенным успехом
        $this->resolve($item);
        return $this;
      }
      
      $promise=$item;
      $item=self::undefined();
      $this->add();

      $promise->then(
        $this->resolve(...), // Первый же успех резолвит общий промис
        function ($reason) use (&$item)
        {
          $item = $reason;
          $this->finish();
        }
      );
    }

    $this->finish();
    return $this;
  }
  
  // Если упали ВСЕ промисы, отклоняем общий промис
  protected function onFinish()
  {
    $this->removeUndefined();
    $errors=$this->list;
    $message= $errors
      ?'All promises were rejected'
      :'An empty array was passed to Promise::any()';
    $this->reject(new AggregateException($message), $errors);
  }
};