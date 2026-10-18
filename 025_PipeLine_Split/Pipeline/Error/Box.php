<?php
class Pipeline_Error_Box
{
  public function __construct(private \Throwable $exception) {}
  public function getException(): \Throwable { return $this->exception; }
}