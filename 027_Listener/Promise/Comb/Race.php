<?
/**
 * Возвращает промис, который завершится так же и с тем же результатом,
 * как и первый завершившийся промис из массива (успех или ошибка).
 */
class Promise_Comb_Race extends Promise_Comb_Base
{
  function process(): self
  {
    // По спецификации пустой race() навечно остается в состоянии PENDING.
    // Но для удобства можно либо выбросить исключение, либо оставить так.
    if (empty($this->list)) return $this;

    foreach ($this->list as $item)
    {
      if (!$item instanceof Promise)
      {
        $this->resolve($item);
        break;
      }

      // Кто первый вызвал метод у deferred, тот и зафиксировал состояние.
      // Повторные вызовы resolve/reject внутри Deferred просто проигнорируются.
      $item->then(
        $this->resolve (...),
        $this->reject  (...)
      );
    }

    return $this;
  }
};