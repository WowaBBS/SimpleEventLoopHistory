<? Return [
<<<'TranslateFrom'
//Чейнинг (Цепочки вызовов)
TranslateFrom
=>
<<<'TranslateTo'
//Chaining (Call chains)
TranslateTo
,
<<<'TranslateFrom'
//Promise::all([...]) - ждет выполнения всех промисов. Если хоть один упал - падает вся цепочка.
TranslateFrom
=>
<<<'TranslateTo'
//Promise::all([...]) - It waits for all promises to resolve. If even one fails, the entire chain fails.
TranslateTo
,
<<<'TranslateFrom'
//Promise::race([...]) - возвращает результат первого завершившегося промиса.
TranslateFrom
=>
<<<'TranslateTo'
//Promise::race([...]) - returns the result of the first promise to settle.
TranslateTo
,
<<<'TranslateFrom'
//Promise::resolve($value) / Promise::reject($reason) - для быстрого создания уже выполненных или отклоненных промисов.
TranslateFrom
=>
<<<'TranslateTo'
//Promise::resolve($value) / Promise::reject($reason) - for quickly creating promises that have already been fulfilled or rejected.
TranslateTo
,
<<<'TranslateFrom'
// Если вернулся другой промис, ждем его
TranslateFrom
=>
<<<'TranslateTo'
// If another promise is returned, we wait for it.
TranslateTo
,
<<<'TranslateFrom'
// Пример интерфейса, к которому стоит стремиться
TranslateFrom
=>
<<<'TranslateTo'
// An example of an interface to aim for
TranslateTo
,
<<<'TranslateFrom'
// Где-то в асинхронном коде:
TranslateFrom
=>
<<<'TranslateTo'
// Somewhere in the asynchronous code:
TranslateTo
,
<<<'TranslateFrom'
// Функция, имитирующая асинхронный запрос (например, к API)
TranslateFrom
=>
<<<'TranslateTo'
// A function simulating an asynchronous request (e.g., to an API)
TranslateTo
,
<<<'TranslateFrom'
// Имитируем некую асинхронную операцию (в реальности тут будет ваш Event Loop)
TranslateFrom
=>
<<<'TranslateTo'
// We simulate an asynchronous operation (in reality, this is where your Event Loop would be).
TranslateTo
,
<<<'TranslateFrom'
// Для примера просто резолвим через секунду
TranslateFrom
=>
<<<'TranslateTo'
// For the sake of example, we simply resolve after a second.
TranslateTo
,
<<<'TranslateFrom'
// Запускаем асинхронный контекст через Fiber
TranslateFrom
=>
<<<'TranslateTo'
// Launching an asynchronous context via a fiber.
TranslateTo
,
<<<'TranslateFrom'
// Магия: код замирает здесь, пока промис не выполнится
TranslateFrom
=>
<<<'TranslateTo'
// The magic: the code pauses here until the promise resolves.
TranslateTo
,
<<<'TranslateFrom'
// Можно делать цепочки зависимых запросов друг за другом:
TranslateFrom
=>
<<<'TranslateTo'
// You can create chains of dependent requests, one after another:
TranslateTo
,
<<<'TranslateFrom'
// Запускаем файбер (он дойдет до Fiber::suspend() внутри await() и вернет управление сюда)
TranslateFrom
=>
<<<'TranslateTo'
// We start the fiber (it will proceed to Fiber::suspend() inside await() and return control here).
TranslateTo
,
<<<'TranslateFrom'
// Имитируем работу Event Loop, который "тикает" и продвигает асинхронные задачи
TranslateFrom
=>
<<<'TranslateTo'
// We simulate the operation of the Event Loop, which "ticks" and advances asynchronous tasks.
TranslateTo
,
<<<'TranslateFrom'
// В реальном приложении это делает класс Loop/EventLoop
TranslateFrom
=>
<<<'TranslateTo'
// In a real-world application, the Loop/EventLoop class handles this.
TranslateTo
,
<<<'TranslateFrom'
// Вызываем тики, чтобы сработал наш асинхронный код
TranslateFrom
=>
<<<'TranslateTo'
// We trigger ticks to make our asynchronous code execute.
TranslateTo
,
<<<'TranslateFrom'
// Если промис уже выполнен, сразу возвращаем значение (без паузы)
TranslateFrom
=>
<<<'TranslateTo'
// If the promise has already been fulfilled, we return the value immediately (without a pause).
TranslateTo
,
<<<'TranslateFrom'
// Проверяем, находимся ли мы внутри файбера
TranslateFrom
=>
<<<'TranslateTo'
// Let's check if we are inside the fiber
TranslateTo
,
<<<'TranslateFrom'
"Метод await() можно вызывать только внутри Fiber."
TranslateFrom
=>
<<<'TranslateTo'
"The await() method can only be called within a Fiber."
TranslateTo
,
<<<'TranslateFrom'
// Подписываемся на этот же промис
TranslateFrom
=>
<<<'TranslateTo'
// We subscribe to the same promise
TranslateTo
,
<<<'TranslateFrom'
// Когда выполнится - возобновляем файбер и передаем результат
TranslateFrom
=>
<<<'TranslateTo'
// Once it completes, we resume the fiber and pass on the result.
TranslateTo
,
<<<'TranslateFrom'
// Если ошибка - возобновляем файбер, бросая в него исключение
TranslateFrom
=>
<<<'TranslateTo'
// If an error occurs, we resume the fiber by throwing an exception into it.
TranslateTo
,
<<<'TranslateFrom'
// Приостанавливаем файбер. Управление возвращается в основной поток (Event Loop)
TranslateFrom
=>
<<<'TranslateTo'
// We suspend the fiber. Control returns to the main thread (Event Loop).
TranslateTo
,
<<<'TranslateFrom'
// Когда вызовется resume() или throw(), код продолжится со следующей строки
TranslateFrom
=>
<<<'TranslateTo'
// When resume() or throw() is called, execution will continue from the next line.
TranslateTo
,
<<<'TranslateFrom'
// Открываем НЕБЛОКИРУЮЩИЙ сокет
TranslateFrom
=>
<<<'TranslateTo'
// Opening a non-blocking socket.
TranslateTo
,
<<<'TranslateFrom'
// Записываем HTTP-запрос (для простоты пишем сразу, в идеале тоже асинхронно через write stream)
TranslateFrom
=>
<<<'TranslateTo'
// We write the HTTP request (for simplicity, we write it directly, though ideally this should also be done asynchronously using a write stream).
TranslateTo
,
<<<'TranslateFrom'
// Регистрируем сокет в нашем Event Loop
TranslateFrom
=>
<<<'TranslateTo'
// We register the socket in our event loop.
TranslateTo
,
<<<'TranslateFrom'
// Поток закрылся (данные кончились)
TranslateFrom
=>
<<<'TranslateTo'
// The stream has closed (data exhausted).
TranslateTo
,
<<<'TranslateFrom'
// Успешно отдаем ответ
TranslateFrom
=>
<<<'TranslateTo'
// Successfully sending the response
TranslateTo
,
<<<'TranslateFrom'
// 1. Запускаем первый асинхронный процесс
TranslateFrom
=>
<<<'TranslateTo'
// 1. Launch the first asynchronous process.
TranslateTo
,
<<<'TranslateFrom'
// Магия Fibers: await() ставит этот файбер на паузу, 
TranslateFrom
=>
<<<'TranslateTo'
// Fibers Magic: await() pauses this fiber,
TranslateTo
,
<<<'TranslateFrom'
// пока Loop качает данные из сокета
TranslateFrom
=>
<<<'TranslateTo'
// while the loop is downloading data from the socket
TranslateTo
,
<<<'TranslateFrom'
// 2. Параллельно запускаем второй процесс
TranslateFrom
=>
<<<'TranslateTo'
// 2. We launch a second process in parallel.
TranslateTo
,
<<<'TranslateFrom'
// Спим без блокирования всего PHP-скрипта
TranslateFrom
=>
<<<'TranslateTo'
// Sleep without blocking the entire PHP script.
TranslateTo
,
<<<'TranslateFrom'
// 3. Запускаем бесконечный цикл событий, который крутит все эти процессы
TranslateFrom
=>
<<<'TranslateTo'
// 3. We start an infinite event loop that drives all these processes.
TranslateTo
,
<<<'TranslateFrom'
// Добавить отложенную задачу (таймер)
TranslateFrom
=>
<<<'TranslateTo'
// Add a scheduled task (timer)
TranslateTo
,
<<<'TranslateFrom'
// Подписаться на появление данных в сокете/потоке
TranslateFrom
=>
<<<'TranslateTo'
// Subscribe to data availability in a socket/stream
TranslateTo
,
<<<'TranslateFrom'
// Отписаться от потока
TranslateFrom
=>
<<<'TranslateTo'
// Unsubscribe from stream
TranslateTo
,
<<<'TranslateFrom'
// Запустить цикл
TranslateFrom
=>
<<<'TranslateTo'
// Start the loop
TranslateTo
,
<<<'TranslateFrom'
// 1. Обрабатываем таймеры, у которых подошло время
TranslateFrom
=>
<<<'TranslateTo'
// 1. Process timers that have expired.
TranslateTo
,
<<<'TranslateFrom'
// 2. Проверяем сокеты через stream_select
TranslateFrom
=>
<<<'TranslateTo'
// 2. Check sockets using stream_select.
TranslateTo
,
<<<'TranslateFrom'
// Таймаут для select: берем время до ближайшего таймера или 1 секунду
TranslateFrom
=>
<<<'TranslateTo'
// Timeout for select: use the time until the nearest timer or 1 second.
TranslateTo
,
<<<'TranslateFrom'
// Ждем системного уведомления о готовности потоков
TranslateFrom
=>
<<<'TranslateTo'
// We are awaiting a system notification regarding the readiness of the streams.
TranslateTo
,
<<<'TranslateFrom'
// Если сокетов нет, просто спим микросекунду, чтобы не грузить CPU на 100%
TranslateFrom
=>
<<<'TranslateTo'
// If there are no sockets, we simply sleep for a microsecond to avoid loading the CPU at 100%.
TranslateTo
,
<<<'TranslateFrom'
// Удобный хелпер для запуска кода в изолированном файбере
TranslateFrom
=>
<<<'TranslateTo'
// A handy helper for running code in an isolated fiber
TranslateTo
,
<<<'TranslateFrom'
// Запуск файбера. Если внутри будет Fiber::suspend(), управление вернется сюда
TranslateFrom
=>
<<<'TranslateTo'
// Starting the fiber. If Fiber::suspend() is called inside, control will return here.
TranslateTo
,
<<<'TranslateFrom'
// ... (предыдущие свойства: state, value, handlers)
TranslateFrom
=>
<<<'TranslateTo'
// ... (previous properties: state, value, handlers)
TranslateTo
,
<<<'TranslateFrom'
// ... (методы delay, addReadStream, removeReadStream остаются прежними)
TranslateFrom
=>
<<<'TranslateTo'
// ... (методы delay, addReadStream, removeReadStream остаются прежними)
TranslateTo
,
<<<'TranslateFrom'
/**
   * Подписка на системный сигнал (например, SIGINT / Ctrl+C)
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Subscribing to a system signal (e.g., SIGINT / Ctrl+C)
   */
TranslateTo
,
<<<'TranslateFrom'
/**
   * Остановка цикла событий
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Stopping the event loop
   */
TranslateTo
,
<<<'TranslateFrom'
/**
   * Инициализация обработчиков под текущую ОС
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Initializing handlers for the current OS
   */
TranslateTo
,
<<<'TranslateFrom'
// --- ВАРИАНТ ДЛЯ WINDOWS ---
TranslateFrom
=>
<<<'TranslateTo'
// --- WINDOWS VERSION ---
TranslateTo
,
<<<'TranslateFrom'
// PHP_WINDOWS_EVENT_CTRL_C - это константа нажатия Ctrl+C под Windows
TranslateFrom
=>
<<<'TranslateTo'
// PHP_WINDOWS_EVENT_CTRL_C is the constant for the Ctrl+C key press on Windows.
TranslateTo
,
<<<'TranslateFrom'
// Имитируем SIGINT (2) для кроссплатформенности
TranslateFrom
=>
<<<'TranslateTo'
// Simulating SIGINT (2) for cross-platform compatibility
TranslateTo
,
<<<'TranslateFrom'
// Говорим ОС, что событие обработано
TranslateFrom
=>
<<<'TranslateTo'
// We tell the OS that the event has been processed.
TranslateTo
,
<<<'TranslateFrom'
// --- ВАРИАНТ ДЛЯ UNIX (Linux, macOS) ---
TranslateFrom
=>
<<<'TranslateTo'
// --- UNIX VERSION (Linux, macOS) ---
TranslateTo
,
<<<'TranslateFrom'
// Регистрируем сигналы SIGINT (Ctrl+C) и SIGTERM (команда kill)
TranslateFrom
=>
<<<'TranslateTo'
// We register SIGINT (Ctrl+C) and SIGTERM (kill command) signals.
TranslateTo
,
<<<'TranslateFrom'
/**
   * Вызов всех зарегистрированных колбэков на этот сигнал
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Invoke all registered callbacks for this signal.
   */
TranslateTo
,
<<<'TranslateFrom'
/**
   * Главный запуск цикла
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Main cycle launch
   */
TranslateTo
,
<<<'TranslateFrom'
// КРИТИЧНО ДЛЯ UNIX: Проверяем, не пришли ли сигналы PCNTL
TranslateFrom
=>
<<<'TranslateTo'
// CRITICAL FOR UNIX: Check for PCNTL signals.
TranslateTo
,
<<<'TranslateFrom'
// Если во время обработки сигнала вызвали Loop::stop(), выходим
TranslateFrom
=>
<<<'TranslateTo'
// If Loop::stop() was called during signal processing, exit.
TranslateTo
,
<<<'TranslateFrom'
// 1. Таймеры
TranslateFrom
=>
<<<'TranslateTo'
// 1. Timers
TranslateTo
,
<<<'TranslateFrom'
// 2. Стримы через stream_select
TranslateFrom
=>
<<<'TranslateTo'
// 2. Streams via stream_select
TranslateTo
,
<<<'TranslateFrom'
// Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
TranslateFrom
=>
<<<'TranslateTo'
// Stream_select error aborts function when receiving system signal on Linux,
TranslateTo
,
<<<'TranslateFrom'
// поэтому глушим ее через @ и проверяем статус
TranslateFrom
=>
<<<'TranslateTo'
// so we silence it using @ and check the status
TranslateTo
,
<<<'TranslateFrom'
// Маленький сон, если задач нет, чтобы не перегружать CPU
TranslateFrom
=>
<<<'TranslateTo'
// A short sleep if there are no tasks, to avoid overloading the CPU.
TranslateTo
,
<<<'TranslateFrom'
// Подписываемся на сигнал SIGINT (Константа 2 - это универсальный SIGINT)
TranslateFrom
=>
<<<'TranslateTo'
// We subscribe to the SIGINT signal (Constant 2 is the universal SIGINT).
TranslateTo
,
<<<'TranslateFrom'
// Создаем массив асинхронных задач
TranslateFrom
=>
<<<'TranslateTo'
// Creating an array of asynchronous tasks
TranslateTo
,
<<<'TranslateFrom'
// ответит через 1 сек
TranslateFrom
=>
<<<'TranslateTo'
// will reply in 1 second
TranslateTo
,
<<<'TranslateFrom'
// ответит через 2 сек
TranslateFrom
=>
<<<'TranslateTo'
// will reply in 2 second
TranslateTo
,
<<<'TranslateFrom'
// Одновременное ожидание всех задач через Fiber await!
TranslateFrom
=>
<<<'TranslateTo'
// Waiting for all tasks simultaneously using Fiber await!
TranslateTo
,
<<<'TranslateFrom'
// Общее время выполнения будет ~2 секунды вместо 4.5 последовательных.
TranslateFrom
=>
<<<'TranslateTo'
// The total execution time will be ~2 seconds instead of 4.5 seconds for sequential execution.
TranslateTo
,
<<<'TranslateFrom'
/**
   * Ожидает выполнения всех промисов в массиве.
   * Возвращает массив результатов в тех же ключах.
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Waits for all promises in the array to complete.
   * Returns an array of results with the same keys.
   */
TranslateTo
,
<<<'TranslateFrom'
// Если передали обычное значение вместо промиса - оборачиваем его
TranslateFrom
=>
<<<'TranslateTo'
// If a regular value is passed instead of a promise, we wrap it.
TranslateTo
,
<<<'TranslateFrom'
// Если упал хотя бы один - реджектим всю пачку (стандартное поведение Promise.all)
TranslateFrom
=>
<<<'TranslateTo'
// If even one fails, we reject the entire batch (standard Promise.all behavior).
TranslateTo
,
<<<'TranslateFrom'
// Команда для безопасного вывода файла в stdout
TranslateFrom
=>
<<<'TranslateTo'
// Command to safely output a file to stdout
TranslateTo
,
<<<'TranslateFrom'
// stdout дочернего процесса
TranslateFrom
=>
<<<'TranslateTo'
// stdout of the child process
TranslateTo
,
<<<'TranslateFrom'
// Открываем процесс
TranslateFrom
=>
<<<'TranslateTo'
// Open process
TranslateTo
,
<<<'TranslateFrom'
// Делаем потоки неблокирующими
TranslateFrom
=>
<<<'TranslateTo'
// Making threads non-blocking
TranslateTo
,
<<<'TranslateFrom'
// Проверяем готовность данных (вызывать в основном Event Loop)
TranslateFrom
=>
<<<'TranslateTo'
// Checking data readiness (primarily called within the event loop)
TranslateTo
,
<<<'TranslateFrom'
// Проверяем, появились ли данные в пайпе без блокировки (таймаут 0)
TranslateFrom
=>
<<<'TranslateTo'
// Check if data has appeared in the pipe without blocking (timeout 0).
TranslateTo
,
<<<'TranslateFrom'
// Закрываем всё
TranslateFrom
=>
<<<'TranslateTo'
// We're closing everything down.
TranslateTo
,
<<<'TranslateFrom'
// Вызываем колбэк
TranslateFrom
=>
<<<'TranslateTo'
// We invoke the callback.
TranslateTo
,
<<<'TranslateFrom'
// Чтение завершено
TranslateFrom
=>
<<<'TranslateTo'
// Reading complete
TranslateTo
,
<<<'TranslateFrom'
// Процесс еще читает файл
TranslateFrom
=>
<<<'TranslateTo'
// The process is still reading the file.
TranslateTo
,
<<<'TranslateFrom'
// Запускаем асинхронное чтение
TranslateFrom
=>
<<<'TranslateTo'
// Starting asynchronous reading.
TranslateTo
,
<<<'TranslateFrom'
// Имитация простейшего Event Loop
TranslateFrom
=>
<<<'TranslateTo'
// Simulating a simple Event Loop
TranslateTo
,
<<<'TranslateFrom'
// Здесь приложение может обрабатывать HTTP-запросы или тики таймера
TranslateFrom
=>
<<<'TranslateTo'
// Here, the application can process HTTP requests or timer ticks.
TranslateTo
,
<<<'TranslateFrom'
// 50мс свободного времени
TranslateFrom
=>
<<<'TranslateTo'
// 50 ms of free time
TranslateTo
,
<<<'TranslateFrom'
" байт\n"
TranslateFrom
=>
<<<'TranslateTo'
" bytes\n"
TranslateTo
,
<<<'TranslateFrom'
// Асинхронное чтение файла
TranslateFrom
=>
<<<'TranslateTo'
// Asynchronous file reading
TranslateTo
,
<<<'TranslateFrom'
"Файл успешно прочитан! Размер: "
TranslateFrom
=>
<<<'TranslateTo'
"File successfully read! Size: "
TranslateTo
,
<<<'TranslateFrom'
" байт.\n"
TranslateFrom
=>
<<<'TranslateTo'
" bytes.\n"
TranslateTo
,
<<<'TranslateFrom'
"Ошибка чтения: "
TranslateFrom
=>
<<<'TranslateTo'
"Read error: "
TranslateTo
,
<<<'TranslateFrom'
"Здесь много текста для проверки асинхронности. "
TranslateFrom
=>
<<<'TranslateTo'
"There is a lot of text here to test for asynchrony. "
TranslateTo
,
<<<'TranslateFrom'
// 1. Создадим тестовый относительно "большой" файл (для примера)
TranslateFrom
=>
<<<'TranslateTo'
// 1. Let's create a relatively "large" test file (as an example).
TranslateTo
,
<<<'TranslateFrom'
// 2. Процесс 1: Быстрый таймер, который должен тикать без задержек
TranslateFrom
=>
<<<'TranslateTo'
// 2. Process 1: A fast timer that must tick without delays.
TranslateTo
,
<<<'TranslateFrom'
// спим 100мс
TranslateFrom
=>
<<<'TranslateTo'
// Sleep for 100ms.
TranslateTo
,
<<<'TranslateFrom'
// 3. Процесс 2: Асинхронное чтение файла
TranslateFrom
=>
<<<'TranslateTo'
// 3. Process 2: Asynchronous file reading
TranslateTo
,
<<<'TranslateFrom'
// Вызываем нашу обертку. Код замирает, но Event Loop продолжает крутиться!
TranslateFrom
=>
<<<'TranslateTo'
// We call our wrapper. The code pauses, but the event loop keeps spinning!
TranslateTo
,
<<<'TranslateFrom'
/**
   * Асинхронно читает весь файл целиком.
   * Возвращает Promise, который резолвится контентом файла.
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Reads the entire file asynchronously.
   * Returns a Promise that resolves with the file content.
   */
TranslateTo
,
<<<'TranslateFrom'
// Открываем файл на чтение в бинарном режиме
TranslateFrom
=>
<<<'TranslateTo'
// Open the file for reading in binary mode.
TranslateTo
,
<<<'TranslateFrom'
// Переводим в неблокирующий режим (на некоторых ОС влияет на поведение буферов)
TranslateFrom
=>
<<<'TranslateTo'
// Switch to non-blocking mode (on some operating systems, this affects buffer behavior).
TranslateTo
,
<<<'TranslateFrom'
// Запускаем рекурсивный процесс чтения через Event Loop
TranslateFrom
=>
<<<'TranslateTo'
// We initiate a recursive read process via the Event Loop.
TranslateTo
,
<<<'TranslateFrom'
// Проверяем, не достигнут ли конец файла
TranslateFrom
=>
<<<'TranslateTo'
// Check whether the end of the file has been reached.
TranslateTo
,
<<<'TranslateFrom'
// Читаем один кусочек
TranslateFrom
=>
<<<'TranslateTo'
// Let's read one excerpt.
TranslateTo
,
<<<'TranslateFrom'
// Вместо того чтобы сразу читать следующий кусок в цикле (что заблокирует поток),
TranslateFrom
=>
<<<'TranslateTo'
// Instead of immediately reading the next fragment in the loop (which blocks the thread),
TranslateTo
,
<<<'TranslateFrom'
// мы откладываем чтение следующего чанка на следующий "тик" Loop.
TranslateFrom
=>
<<<'TranslateTo'
// We defer reading the next chunk until the next Loop "tick".
TranslateTo
,
<<<'TranslateFrom'
// Таймаут 0 означает: "выполни как можно скорее, но дай сначала подышать другим задачам".
TranslateFrom
=>
<<<'TranslateTo'
// A timeout of 0 means: "execute as soon as possible, but let other tasks breathe first."
TranslateTo
,
<<<'TranslateFrom'
// Проверяем, не завершился ли процесс
TranslateFrom
=>
<<<'TranslateTo'
// We check whether the process has finished.
TranslateTo
,
<<<'TranslateFrom'
// 1. Указываем путь к файлу
TranslateFrom
=>
<<<'TranslateTo'
// 1. Specify the path to the file.
TranslateTo
,
<<<'TranslateFrom'
// Проверяем, существует ли файл и доступен ли для чтения
TranslateFrom
=>
<<<'TranslateTo'
// Check if the file exists and is readable.
TranslateTo
,
<<<'TranslateFrom'
// 2. Открываем файл в режиме чтения ('r')
TranslateFrom
=>
<<<'TranslateTo'
// 2. Open the file in read mode ('r')
TranslateTo
,
<<<'TranslateFrom'
// 3. Читаем файл построчно до самого конца (EOF)
TranslateFrom
=>
<<<'TranslateTo'
// 3. Read the file line by line until the end (EOF).
TranslateTo
,
<<<'TranslateFrom'
// Выводим строку напрямую в консоль
TranslateFrom
=>
<<<'TranslateTo'
// Output the string directly to the console.
TranslateTo
,
<<<'TranslateFrom'
// 4. Обязательно закрываем дескриптор файла
TranslateFrom
=>
<<<'TranslateTo'
// 4. Be sure to close the file descriptor.
TranslateTo
,
<<<'TranslateFrom'
/*
// Запускаем асинхронное чтение
//$FileName='large_file.log';
//$FileName=__FILE__;
$FileName=$LargeFileName;

$reader = new FileReaderExec($FileName, function($data, $error) {
  if ($error) 
    echo "Error: $error\n";
  else 
    echo "File was readed async! Length: " . strlen($data) . "\n";
});

echo "Main thread is free and is doing another job...\n";

// Имитация простейшего Event Loop
while ($reader->tick())
{
  // Здесь приложение может обрабатывать HTTP-запросы или тики таймера
  echo "."; 
  usleep(50000); // 50мс свободного времени
}
*/
TranslateFrom
=>
<<<'TranslateTo'
/*
// Запускаем асинхронное чтение
//$FileName='large_file.log';
//$FileName=__FILE__;
$FileName=$LargeFileName;

$reader = new FileReaderExec($FileName, function($data, $error) {
  if ($error) 
    echo "Error: $error\n";
  else 
    echo "File was readed async! Length: " . strlen($data) . "\n";
});

echo "Main thread is free and is doing another job...\n";

// Имитация простейшего Event Loop
while ($reader->tick())
{
  // Здесь приложение может обрабатывать HTTP-запросы или тики таймера
  echo "."; 
  usleep(50000); // 50мс свободного времени
}
*/
TranslateTo
,
<<<'TranslateFrom'
// 2. Проверяем стримы через stream_select
TranslateFrom
=>
<<<'TranslateTo'
// 2. Checking streams using stream_select
TranslateTo
,
<<<'TranslateFrom'
// Сервер 1: сразу падает с ошибкой 500
TranslateFrom
=>
<<<'TranslateTo'
// Server 1: crashes immediately with a 500 error.
TranslateTo
,
<<<'TranslateFrom'
// Сервер 2: успешный, но медленный (ответит через 2 секунды)
TranslateFrom
=>
<<<'TranslateTo'
// Server 2: successful but slow (responds in 2 seconds)
TranslateTo
,
<<<'TranslateFrom'
// Сервер 3: успешный и быстрый (ответит через 0.5 секунды)
TranslateFrom
=>
<<<'TranslateTo'
// Server 3: successful and fast (responds in 0.5 seconds)
TranslateTo
,
<<<'TranslateFrom'
// Ждем первый успешный ответ. Ошибка сервера 1 будет проигнорирована!
TranslateFrom
=>
<<<'TranslateTo'
// Waiting for the first successful response. Server error 1 will be ignored!
TranslateTo
,
<<<'TranslateFrom'
// Выведет данные с сервера 3
TranslateFrom
=>
<<<'TranslateTo'
// Will output data from server 3
TranslateTo
,
<<<'TranslateFrom'
// Запускаем наш Event Loop для обработки обоих кейсов
TranslateFrom
=>
<<<'TranslateTo'
// We launch our event loop to handle both cases.
TranslateTo
,
<<<'TranslateFrom'
/**
   * Возвращает промис, который завершится так же и с тем же результатом,
   * как и первый завершившийся промис из массива (успех или ошибка).
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Returns a promise that will resolve in the same way and with the same result,
   * just like the first promise from the array to settle (whether successfully or with an error).
   */
TranslateTo
,
<<<'TranslateFrom'
// По спецификации пустой race() навечно остается в состоянии PENDING.
TranslateFrom
=>
<<<'TranslateTo'
// According to the specification, an empty race() remains in the PENDING state forever.
TranslateTo
,
<<<'TranslateFrom'
// Но для удобства можно либо выбросить исключение, либо оставить так.
TranslateFrom
=>
<<<'TranslateTo'
// However, for convenience, you can either throw an exception or leave it as is.
TranslateTo
,
<<<'TranslateFrom'
// Кто первый вызвал метод у deferred, тот и зафиксировал состояние.
TranslateFrom
=>
<<<'TranslateTo'
// Whoever called a method on the deferred object first is the one who locked in the state.
TranslateTo
,
<<<'TranslateFrom'
// Повторные вызовы resolve/reject внутри Deferred просто проигнорируются.
TranslateFrom
=>
<<<'TranslateTo'
// Subsequent calls to resolve/reject within a Deferred are simply ignored.
TranslateTo
,
<<<'TranslateFrom'
/**
   * Возвращает промис, который выполнится успешно, как только выполнится успешно
   * хотя бы один из промисов в массиве.
   * Если все промисы отклонены, возвращает ошибку AggregateException.
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Returns a promise that resolves as soon as
   * at least one of the promises in the array resolves. 
   * If all promises are rejected, returns an AggregateException.
   */
TranslateTo
,
<<<'TranslateFrom'
"Promise::any() передан пустой массив."
TranslateFrom
=>
<<<'TranslateTo'
"An empty array was passed to Promise::any()."
TranslateTo
,
<<<'TranslateFrom'
// Обычное значение считается мгновенным успехом
TranslateFrom
=>
<<<'TranslateTo'
// The standard value is considered an instant success.
TranslateTo
,
<<<'TranslateFrom'
// Первый же успех резолвит общий промис
TranslateFrom
=>
<<<'TranslateTo'
// The first success resolves the general promise
TranslateTo
,
<<<'TranslateFrom'
// Если упали ВСЕ промисы, отклоняем общий промис
TranslateFrom
=>
<<<'TranslateTo'
// If all promises have failed, reject the overall promise.
TranslateTo
,
<<<'TranslateFrom'
"Все промисы были отклонены"
TranslateFrom
=>
<<<'TranslateTo'
"All promises were rejected."
TranslateTo
,
<<<'TranslateFrom'
/**
 * Кастомное исключение для метода Promise::any(),
 * хранящее ошибки всех упавших промисов.
 */
TranslateFrom
=>
<<<'TranslateTo'
/**
 * A custom exception for the Promise::any() method,
 * storing the errors of all rejected promises.
 */
TranslateTo
,
<<<'TranslateFrom'
// Имитируем запрос, который длится 3 секунды
TranslateFrom
=>
<<<'TranslateTo'
// We simulate a request that takes 3 seconds.
TranslateTo
,
<<<'TranslateFrom'
// Создаем промис-таймаут, который упадет через 1.5 секунды
TranslateFrom
=>
<<<'TranslateTo'
// We create a promise timeout that rejects after 1.5 seconds.
TranslateTo
,
<<<'TranslateFrom'
// Запускаем гонку!
TranslateFrom
=>
<<<'TranslateTo'
// We're kicking off the race!
TranslateTo
,
<<<'TranslateFrom'
// Сработает таймаут, так как 1.5с < 3.0с
TranslateFrom
=>
<<<'TranslateTo'
// The timeout will trigger, since 1.5s < 3.0s.
TranslateTo
,
<<<'TranslateFrom'
/**
   * Ограничивает время выполнения текущего промиса.
   * Если промис не успевает выполниться за $seconds секунд, возвращается отклонённый промис.
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Limits the execution time of the current promise.
   * If the promise does not resolve within $seconds seconds, a rejected promise is returned.
   */
TranslateTo
,
<<<'TranslateFrom'
// Создаем промис-таймер, который гарантированно упадет через указанное время.
TranslateFrom
=>
<<<'TranslateTo'
// We create a promise-based timer that is guaranteed to reject after the specified time.
TranslateTo
,
<<<'TranslateFrom'
// Для создания таймера используем наш Loop::delay.
TranslateFrom
=>
<<<'TranslateTo'
// To create a timer, we use our Loop::delay.
TranslateTo
,
<<<'TranslateFrom'
// Запускаем гонку между текущим промисом ($this) и промисом-таймером.
TranslateFrom
=>
<<<'TranslateTo'
// We start a race between the current promise ($this) and the timer promise.
TranslateTo
,
<<<'TranslateFrom'
// Возвращаем результат этой гонки.
TranslateFrom
=>
<<<'TranslateTo'
// We return the result of this race.
TranslateTo
,
<<<'TranslateFrom'
// Имитируем долгий сетевой запрос к API
TranslateFrom
=>
<<<'TranslateTo'
// Simulating a long-running network request to the API.
TranslateTo
,
<<<'TranslateFrom'
// Ответ придет только через 3 секунды
TranslateFrom
=>
<<<'TranslateTo'
// The answer will arrive only after 3 seconds.
TranslateTo
,
<<<'TranslateFrom'
// Процесс 1: Запрос, который НЕ уложится в таймаут
TranslateFrom
=>
<<<'TranslateTo'
// Process 1: A request that will NOT time out.
TranslateTo
,
<<<'TranslateFrom'
// Просто вызываем метод цепочкой прямо у промиса!
TranslateFrom
=>
<<<'TranslateTo'
// We simply call the method in a chain directly on the promise!
TranslateTo
,
<<<'TranslateFrom'
// Сработает этот блок, так как 1.0с < 3.0с
TranslateFrom
=>
<<<'TranslateTo'
// This block will execute, since 1.0s < 3.0s.
TranslateTo
,
<<<'TranslateFrom'
// Процесс 2: Запрос, который УЛОЖИТСЯ в таймаут (дали ему 4 секунды)
TranslateFrom
=>
<<<'TranslateTo'
// Process 2: A request that fits within the timeout (it was given 4 seconds)
TranslateTo
,
<<<'TranslateFrom'
// Немного подождем, чтобы логи не перемешивались слишком хаотично
TranslateFrom
=>
<<<'TranslateTo'
// Let's wait a bit so the logs don't get mixed up too chaotically.
TranslateTo
,
<<<'TranslateFrom'
// Можно кастомизировать текст ошибки при желании
TranslateFrom
=>
<<<'TranslateTo'
// You can customize the error text if desired.
TranslateTo
,
<<<'TranslateFrom'
// Запуск цикла событий
TranslateFrom
=>
<<<'TranslateTo'
// Starting the event loop
TranslateTo
,
<<<'TranslateFrom'
// Успешно вернет баланс
TranslateFrom
=>
<<<'TranslateTo'
// It will successfully restore balance.
TranslateTo
,
<<<'TranslateFrom'
// Упадет
TranslateFrom
=>
<<<'TranslateTo'
// It will fall
TranslateTo
,
<<<'TranslateFrom'
// Метод allSettled() никогда не выбросит исключение в await!
TranslateFrom
=>
<<<'TranslateTo'
// The allSettled() method will never throw an exception when awaited!
TranslateTo
,
<<<'TranslateFrom'
// В поле 'reason' лежит Throwable или строка ошибки
TranslateFrom
=>
<<<'TranslateTo'
// The 'reason' field contains a Throwable or an error string.
TranslateTo
,
<<<'TranslateFrom'
// Запускаем Event Loop
TranslateFrom
=>
<<<'TranslateTo'
// Starting the Event Loop
TranslateTo
,
<<<'TranslateFrom'
/**
   * Ожидает завершения всех промисов (успешного или с ошибкой).
   * Возвращает массив структур: 
   * ['status' => 'fulfilled', 'value' => $value] или ['status' => 'rejected', 'reason' => $reason]
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Waits for all promises to complete (successfully or with an error).
   * Returns an array of structures:
   * ['status' => 'fulfilled', 'value' => $value] или ['status' => 'rejected', 'reason' => $reason]
   */
TranslateTo
,
<<<'TranslateFrom'
// Если передали обычное значение - это моментальный успех
TranslateFrom
=>
<<<'TranslateTo'
// If you convey the ordinary meaning, it's an instant success.
TranslateTo
,
<<<'TranslateFrom'
// Нам важен сам факт завершения, поэтому логика сбора идентична для обоих колбэков
TranslateFrom
=>
<<<'TranslateTo'
// The fact of completion itself is what matters to us, so the collection logic is identical for both callbacks.
TranslateTo
,
<<<'TranslateFrom'
/**
 * Управляющий объект для отмены задач
 */
TranslateFrom
=>
<<<'TranslateTo'
/**
 * Task cancellation manager
 */
TranslateTo
,
<<<'TranslateFrom'
/**
 * Токен отмены, передаваемый внутрь асинхронных функций
 */
TranslateFrom
=>
<<<'TranslateTo'
/**
 * Cancellation token passed into asynchronous functions
 */
TranslateTo
,
<<<'TranslateFrom'
/**
   * Позволяет асинхронной функции подписаться на событие отмены
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Allows an asynchronous function to subscribe to a cancellation event.
   */
TranslateTo
,
<<<'TranslateFrom'
/**
   * Внутренний метод, вызываемый только из AbortController
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Internal method called only from AbortController
   */
TranslateTo
,
<<<'TranslateFrom'
"Операция была отменена."
TranslateFrom
=>
<<<'TranslateTo'
"The operation was cancelled."
TranslateTo
,
<<<'TranslateFrom'
// Подготовим файл
TranslateFrom
=>
<<<'TranslateTo'
// We will prepare the file.
TranslateTo
,
<<<'TranslateFrom'
// 1. Создаем контроллер отмены
TranslateFrom
=>
<<<'TranslateTo'
// 1. Create a cancellation controller.
TranslateTo
,
<<<'TranslateFrom'
// 2. Имитируем внешнее событие: через 20 миллисекунд отменяем операцию
TranslateFrom
=>
<<<'TranslateTo'
// 2. Simulate an external event: cancel the operation after 20 milliseconds.
TranslateTo
,
<<<'TranslateFrom'
// Передаем сигнал и в саму операцию, и в await
TranslateFrom
=>
<<<'TranslateTo'
// We pass the signal both to the operation itself and to the await.
TranslateTo
,
<<<'TranslateFrom'
// Ловим именно наше кастомное исключение отмены
TranslateFrom
=>
<<<'TranslateTo'
// We catch specifically our custom cancellation exception.
TranslateTo
,
<<<'TranslateFrom'
/**
 * Исключение, выбрасываемое при отмене операции
 */
TranslateFrom
=>
<<<'TranslateTo'
/**
 * Exception thrown when an operation is cancelled
 */
TranslateTo
,
<<<'TranslateFrom'
// Флаг для внутренней остановки рекурсии
TranslateFrom
=>
<<<'TranslateTo'
// Flag for internal recursion termination
TranslateTo
,
<<<'TranslateFrom'
// Если была отмена, выходим
TranslateFrom
=>
<<<'TranslateTo'
// If there was a cancellation, we exit.
TranslateTo
,
<<<'TranslateFrom'
// 1. Проверяем, не отменено ли всё еще до начала ожидания
TranslateFrom
=>
<<<'TranslateTo'
// 1. Check whether cancellation has already occurred before the wait begins.
TranslateTo
,
<<<'TranslateFrom'
// 2. Если передан сигнал, подписываемся на него
TranslateFrom
=>
<<<'TranslateTo'
// 2. If a signal is transmitted, we subscribe to it.
TranslateTo
,
<<<'TranslateFrom'
// Если файбер все еще на паузе, бросаем в него исключение отмены
TranslateFrom
=>
<<<'TranslateTo'
// If the fiber is still paused, throw a cancellation exception into it.
TranslateTo
,
<<<'TranslateFrom'
// Колбэк, который вызывается при отмене для очистки ресурсов (стримов, таймеров)
TranslateFrom
=>
<<<'TranslateTo'
// A callback invoked upon cancellation to clean up resources (streams, timers).
TranslateTo
,
<<<'TranslateFrom'
// Метод для установки колбэка очистки (используется внутри AsyncFile/httpGet)
TranslateFrom
=>
<<<'TranslateTo'
// Method for setting a cleanup callback (used within AsyncFile/httpGet)
TranslateTo
,
<<<'TranslateFrom'
/**
   * Принудительная отмена конкретного промиса
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Forced cancellation of a specific promise
   */
TranslateTo
,
<<<'TranslateFrom'
"Промис был отменен."
TranslateFrom
=>
<<<'TranslateTo'
"The promise was cancelled."
TranslateTo
,
<<<'TranslateFrom'
// 1. Вызываем очистку системных ресурсов, если она была задана
TranslateFrom
=>
<<<'TranslateTo'
// 1. Invoke system resource cleanup if it has been specified.
TranslateTo
,
<<<'TranslateFrom'
// 2. Отклоняем промис с ошибкой AbortException
TranslateFrom
=>
<<<'TranslateTo'
// 2. Reject the promise with an AbortException.
TranslateTo
,
<<<'TranslateFrom'
/**
 * Токен отмены, передаваемый внутрь асинхронных функций или шлёт событие отмены промисам.
 */
TranslateFrom
=>
<<<'TranslateTo'
/**
 * A cancellation token passed into asynchronous functions or used to send a cancellation event to promises.
 */
TranslateTo
,
<<<'TranslateFrom'
// Храним связанные промисы
TranslateFrom
=>
<<<'TranslateTo'
// We store the associated promises.
TranslateTo
,
<<<'TranslateFrom'
/**
   * Позволяет старой асинхронной функции подписаться на событие отмены
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Allows an old asynchronous function to subscribe to a cancellation event.
   */
TranslateTo
,
<<<'TranslateFrom'
/**
   * Привязывает промис к этому сигналу.
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Binds the promise to this signal.
   */
TranslateTo
,
<<<'TranslateFrom'
// 1. Оповещаем обычных слушателей
TranslateFrom
=>
<<<'TranslateTo'
// 1. Notifying general listeners
TranslateTo
,
<<<'TranslateFrom'
// 2. Раздаем сигнал отмены всем привязанным промисам!
TranslateFrom
=>
<<<'TranslateTo'
// 2. Broadcast the cancellation signal to all linked promises!
TranslateTo
,
<<<'TranslateFrom'
// Регистрируем логику отмены прямо в промисе!
TranslateFrom
=>
<<<'TranslateTo'
// We register the cancellation logic right inside the promise!
TranslateTo
,
<<<'TranslateFrom'
// Подготовим тестовые файлы
TranslateFrom
=>
<<<'TranslateTo'
// We will prepare the test files.
TranslateTo
,
<<<'TranslateFrom'
// Создаем промисы чтения файлов
TranslateFrom
=>
<<<'TranslateTo'
// Creating file-reading promises
TranslateTo
,
<<<'TranslateFrom'
// Навешиваем их на один AbortController / AbortSignal
TranslateFrom
=>
<<<'TranslateTo'
// We attach them to a single AbortController / AbortSignal.
TranslateTo
,
<<<'TranslateFrom'
// Имитируем отмену всей группы задач через 15 миллисекунд
TranslateFrom
=>
<<<'TranslateTo'
// We simulate the cancellation of the entire task group after 15 milliseconds.
TranslateTo
,
<<<'TranslateFrom'
// Функция для безопасного await
TranslateFrom
=>
<<<'TranslateTo'
// Function for safe await
TranslateTo
,
<<<'TranslateFrom'
// Запускаем ожидание
TranslateFrom
=>
<<<'TranslateTo'
// Starting the wait process.
TranslateTo
,
<<<'TranslateFrom'
// Создаем промисы чтения файлов и навешиваем их на один AbortController / AbortSignal
TranslateFrom
=>
<<<'TranslateTo'
// We create file-reading promises and attach them to a single AbortController / AbortSignal.
TranslateTo
,
<<<'TranslateFrom'
/**
   * Привязывает этот промис к указанному сигналу отмены.
   * Возвращает текущий промис для чейнинга (Fluent API).
   */
TranslateFrom
=>
<<<'TranslateTo'
/**
   * Binds this promise to the specified cancellation signal.
   * Returns the current promise for chaining (Fluent API).
   */
TranslateTo
,
];