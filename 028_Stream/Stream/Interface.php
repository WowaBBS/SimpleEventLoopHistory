<?
interface IStream
{
//****************************************************************
// Read
  function recv(int $size): string;
//function isReading(): bool;
  function resumeRead(): void;
  function pauseRead(): void;
  
//****************************************************************
// Write
  function waitWrite(): void;
  function send(string $data): bool;

//****************************************************************
// Time life
  function close();
}
