<?
/**
 * Кастомное исключение для метода Promise::any(),
 * хранящее ошибки всех упавших промисов.
 */
class AggregateException extends \RuntimeException
{
  private array $errors;

  function __construct(string $message, array $errors = [])
  {
    parent::__construct($message);
    $this->errors = $errors;
  }

  function getErrors(): array { return $this->errors; }
}