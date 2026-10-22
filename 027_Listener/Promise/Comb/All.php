<?
/**
 * Ожидает выполнения всех промисов в массиве.
 * Возвращает массив результатов в тех же ключах.
 */
class Promise_Comb_All extends Promise_Comb_Base
{
  function process(): self
  {
    foreach ($this->list as &$item)
    {
      if (!($item instanceof Promise)) continue;
      
      if ($item->isRejected())
      {
        $this->reject($item->getReason());
        return $this;
      }
      
      if ($item->isFulfilled())
      {
        $item = $item->value; //getValue()
        continue;
      }
      
      $this->add();

      $item->then(
        function ($value) use (&$item)
        {
          $item = $value;
          $this->finish();
        },
        // Если упал хотя бы один - реджектим всю пачку (стандартное поведение Promise.all)
        $this->reject(...)
      );
    }
    
    $this->finish();

    return $this;
  }
  
  protected function onFinish() { $this->resolve($this->list); }
};