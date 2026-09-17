<? Return [//PHPFormatter: Translate file
[// ---------------- Promise.Chain.Test.php8
<<<'TranslateFrom'
//Чейнинг (Цепочки вызовов)
TranslateFrom,
<<<'TranslateTo'
//Chaining (Call chains)
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
//Promise::all([...]) - ждет выполнения всех промисов. Если хоть один упал - падает вся цепочка.
TranslateFrom,
<<<'TranslateTo'
//Promise::all([...]) - It waits for all promises to resolve. If even one fails, the entire chain fails.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
//Promise::race([...]) - возвращает результат первого завершившегося промиса.
TranslateFrom,
<<<'TranslateTo'
//Promise::race([...]) - returns the result of the first promise to settle.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
//Promise::resolve($value) / Promise::reject($reason) - для быстрого создания уже выполненных или отклоненных промисов.
TranslateFrom,
<<<'TranslateTo'
//Promise::resolve($value) / Promise::reject($reason) - for quickly creating promises that have already been fulfilled or rejected.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Если вернулся другой промис, ждем его
TranslateFrom,
<<<'TranslateTo'
// If another promise is returned, we wait for it.
TranslateTo],
[// ---------------- Promise.Simple.Test.php8
<<<'TranslateFrom'
// Пример интерфейса, к которому стоит стремиться
TranslateFrom,
<<<'TranslateTo'
// An example of an interface to aim for
TranslateTo],
[// ---------------- Promise.Simple.Test.php8
<<<'TranslateFrom'
// Где-то в асинхронном коде:
TranslateFrom,
<<<'TranslateTo'
// Somewhere in the asynchronous code:
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Функция, имитирующая асинхронный запрос (например, к API)
TranslateFrom,
<<<'TranslateTo'
// A function simulating an asynchronous request (e.g., to an API)
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Имитируем некую асинхронную операцию (в реальности тут будет ваш Event Loop)
TranslateFrom,
<<<'TranslateTo'
// We simulate an asynchronous operation (in reality, this is where your Event Loop would be).
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Для примера просто резолвим через секунду
TranslateFrom,
<<<'TranslateTo'
// For the sake of example, we simply resolve after a second.
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Запускаем асинхронный контекст через Fiber
TranslateFrom,
<<<'TranslateTo'
// Launching an asynchronous context via a fiber.
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Магия: код замирает здесь, пока промис не выполнится
TranslateFrom,
<<<'TranslateTo'
// The magic: the code pauses here until the promise resolves.
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Можно делать цепочки зависимых запросов друг за другом:
TranslateFrom,
<<<'TranslateTo'
// You can create chains of dependent requests, one after another:
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Запускаем файбер (он дойдет до Fiber::suspend() внутри await() и вернет управление сюда)
TranslateFrom,
<<<'TranslateTo'
// We start the fiber (it will proceed to Fiber::suspend() inside await() and return control here).
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Имитируем работу Event Loop, который "тикает" и продвигает асинхронные задачи
TranslateFrom,
<<<'TranslateTo'
// We simulate the operation of the Event Loop, which "ticks" and advances asynchronous tasks.
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// В реальном приложении это делает класс Loop/EventLoop
TranslateFrom,
<<<'TranslateTo'
// In a real-world application, the Loop/EventLoop class handles this.
TranslateTo],
[// ---------------- Fiber.Test.php8
<<<'TranslateFrom'
// Вызываем тики, чтобы сработал наш асинхронный код
TranslateFrom,
<<<'TranslateTo'
// We trigger ticks to make our asynchronous code execute.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Если промис уже выполнен, сразу возвращаем значение (без паузы)
TranslateFrom,
<<<'TranslateTo'
// If the promise has already been fulfilled, we return the value immediately (without a pause).
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Проверяем, находимся ли мы внутри файбера
TranslateFrom,
<<<'TranslateTo'
// Let's check if we are inside the fiber
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
"Метод await() можно вызывать только внутри Fiber."
TranslateFrom,
<<<'TranslateTo'
"The await() method can only be called within a Fiber."
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Подписываемся на этот же промис
TranslateFrom,
<<<'TranslateTo'
// We subscribe to the same promise
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Когда выполнится - возобновляем файбер и передаем результат
TranslateFrom,
<<<'TranslateTo'
// Once it completes, we resume the fiber and pass on the result.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Если ошибка - возобновляем файбер, бросая в него исключение
TranslateFrom,
<<<'TranslateTo'
// If an error occurs, we resume the fiber by throwing an exception into it.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Приостанавливаем файбер. Управление возвращается в основной поток (Event Loop)
TranslateFrom,
<<<'TranslateTo'
// We suspend the fiber. Control returns to the main thread (Event Loop).
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Когда вызовется resume() или throw(), код продолжится со следующей строки
TranslateFrom,
<<<'TranslateTo'
// When resume() or throw() is called, execution will continue from the next line.
TranslateTo],
[// ---------------- Http.php
<<<'TranslateFrom'
// Открываем НЕБЛОКИРУЮЩИЙ сокет
TranslateFrom,
<<<'TranslateTo'
// Opening a non-blocking socket.
TranslateTo],
[// ---------------- Http.php
<<<'TranslateFrom'
// Записываем HTTP-запрос (для простоты пишем сразу, в идеале тоже асинхронно через write stream)
TranslateFrom,
<<<'TranslateTo'
// We write the HTTP request (for simplicity, we write it directly, though ideally this should also be done asynchronously using a write stream).
TranslateTo],
[// ---------------- Http.php
<<<'TranslateFrom'
// Регистрируем сокет в нашем Event Loop
TranslateFrom,
<<<'TranslateTo'
// We register the socket in our event loop.
TranslateTo],
[// ---------------- Http.php
<<<'TranslateFrom'
// Поток закрылся (данные кончились)
TranslateFrom,
<<<'TranslateTo'
// The stream has closed (data exhausted).
TranslateTo],
[// ---------------- Http.php, FileReaderExec.php
<<<'TranslateFrom'
// Успешно отдаем ответ
TranslateFrom,
<<<'TranslateTo'
// Successfully sending the response
TranslateTo],
[// ---------------- Loop.Async.Test.php8
<<<'TranslateFrom'
// 1. Запускаем первый асинхронный процесс
TranslateFrom,
<<<'TranslateTo'
// 1. Launch the first asynchronous process.
TranslateTo],
[// ---------------- Loop.Async.Test.php8
<<<'TranslateFrom'
// Магия Fibers: await() ставит этот файбер на паузу, 
TranslateFrom,
<<<'TranslateTo'
// Fibers Magic: await() pauses this fiber,
TranslateTo],
[// ---------------- Loop.Async.Test.php8
<<<'TranslateFrom'
// пока Loop качает данные из сокета
TranslateFrom,
<<<'TranslateTo'
// while the loop is downloading data from the socket
TranslateTo],
[// ---------------- Loop.Async.Test.php8
<<<'TranslateFrom'
// 2. Параллельно запускаем второй процесс
TranslateFrom,
<<<'TranslateTo'
// 2. We launch a second process in parallel.
TranslateTo],
[// ---------------- Loop.Async.Test.php8
<<<'TranslateFrom'
// Спим без блокирования всего PHP-скрипта
TranslateFrom,
<<<'TranslateTo'
// Sleep without blocking the entire PHP script.
TranslateTo],
[// ---------------- Loop.Async.Test.php8
<<<'TranslateFrom'
// 3. Запускаем бесконечный цикл событий, который крутит все эти процессы
TranslateFrom,
<<<'TranslateTo'
// 3. We start an infinite event loop that drives all these processes.
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Добавить отложенную задачу (таймер)
TranslateFrom,
<<<'TranslateTo'
// Add a scheduled task (timer)
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Подписаться на появление данных в сокете/потоке
TranslateFrom,
<<<'TranslateTo'
// Subscribe to data availability in a socket/stream
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Отписаться от потока
TranslateFrom,
<<<'TranslateTo'
// Unsubscribe from stream
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Запустить цикл
TranslateFrom,
<<<'TranslateTo'
// Start the loop
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// 1. Обрабатываем таймеры, у которых подошло время
TranslateFrom,
<<<'TranslateTo'
// 1. Process timers that have expired.
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// 2. Проверяем сокеты через stream_select
TranslateFrom,
<<<'TranslateTo'
// 2. Check sockets using stream_select.
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Таймаут для select: берем время до ближайшего таймера или 1 секунду
TranslateFrom,
<<<'TranslateTo'
// Timeout for select: use the time until the nearest timer or 1 second.
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Ждем системного уведомления о готовности потоков
TranslateFrom,
<<<'TranslateTo'
// We are awaiting a system notification regarding the readiness of the streams.
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Если сокетов нет, просто спим микросекунду, чтобы не грузить CPU на 100%
TranslateFrom,
<<<'TranslateTo'
// If there are no sockets, we simply sleep for a microsecond to avoid loading the CPU at 100%.
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Удобный хелпер для запуска кода в изолированном файбере
TranslateFrom,
<<<'TranslateTo'
// A handy helper for running code in an isolated fiber
TranslateTo],
[// ---------------- Loop.php, Loop1.php
<<<'TranslateFrom'
// Запуск файбера. Если внутри будет Fiber::suspend(), управление вернется сюда
TranslateFrom,
<<<'TranslateTo'
// Starting the fiber. If Fiber::suspend() is called inside, control will return here.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// ... (предыдущие свойства: state, value, handlers)
TranslateFrom,
<<<'TranslateTo'
// ... (previous properties: state, value, handlers)
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// ... (методы delay, addReadStream, removeReadStream остаются прежними)
TranslateFrom,
<<<'TranslateTo'
// ... (методы delay, addReadStream, removeReadStream остаются прежними)
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
/**
   * Подписка на системный сигнал (например, SIGINT / Ctrl+C)
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Subscribing to a system signal (e.g., SIGINT / Ctrl+C)
   */
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
/**
   * Остановка цикла событий
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Stopping the event loop
   */
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
/**
   * Инициализация обработчиков под текущую ОС
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Initializing handlers for the current OS
   */
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// --- ВАРИАНТ ДЛЯ WINDOWS ---
TranslateFrom,
<<<'TranslateTo'
// --- WINDOWS VERSION ---
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// PHP_WINDOWS_EVENT_CTRL_C - это константа нажатия Ctrl+C под Windows
TranslateFrom,
<<<'TranslateTo'
// PHP_WINDOWS_EVENT_CTRL_C is the constant for the Ctrl+C key press on Windows.
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// Имитируем SIGINT (2) для кроссплатформенности
TranslateFrom,
<<<'TranslateTo'
// Simulating SIGINT (2) for cross-platform compatibility
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// Говорим ОС, что событие обработано
TranslateFrom,
<<<'TranslateTo'
// We tell the OS that the event has been processed.
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// --- ВАРИАНТ ДЛЯ UNIX (Linux, macOS) ---
TranslateFrom,
<<<'TranslateTo'
// --- UNIX VERSION (Linux, macOS) ---
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// Регистрируем сигналы SIGINT (Ctrl+C) и SIGTERM (команда kill)
TranslateFrom,
<<<'TranslateTo'
// We register SIGINT (Ctrl+C) and SIGTERM (kill command) signals.
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
/**
   * Вызов всех зарегистрированных колбэков на этот сигнал
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Invoke all registered callbacks for this signal.
   */
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
/**
   * Главный запуск цикла
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Main cycle launch
   */
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// КРИТИЧНО ДЛЯ UNIX: Проверяем, не пришли ли сигналы PCNTL
TranslateFrom,
<<<'TranslateTo'
// CRITICAL FOR UNIX: Check for PCNTL signals.
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// Если во время обработки сигнала вызвали Loop::stop(), выходим
TranslateFrom,
<<<'TranslateTo'
// If Loop::stop() was called during signal processing, exit.
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// 1. Таймеры
TranslateFrom,
<<<'TranslateTo'
// 1. Timers
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// 2. Стримы через stream_select
TranslateFrom,
<<<'TranslateTo'
// 2. Streams via stream_select
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
TranslateFrom,
<<<'TranslateTo'
// Stream_select error aborts function when receiving system signal on Linux,
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// поэтому глушим ее через @ и проверяем статус
TranslateFrom,
<<<'TranslateTo'
// so we silence it using @ and check the status
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// Маленький сон, если задач нет, чтобы не перегружать CPU
TranslateFrom,
<<<'TranslateTo'
// A short sleep if there are no tasks, to avoid overloading the CPU.
TranslateTo],
[// ---------------- Loop.Signal.Test.php8
<<<'TranslateFrom'
// Подписываемся на сигнал SIGINT (Константа 2 - это универсальный SIGINT)
TranslateFrom,
<<<'TranslateTo'
// We subscribe to the SIGINT signal (Constant 2 is the universal SIGINT).
TranslateTo],
[// ---------------- Loop.Signal.Test.php8
<<<'TranslateFrom'
// Создаем массив асинхронных задач
TranslateFrom,
<<<'TranslateTo'
// Creating an array of asynchronous tasks
TranslateTo],
[// ---------------- Loop.Signal.Test.php8
<<<'TranslateFrom'
// ответит через 1 сек
TranslateFrom,
<<<'TranslateTo'
// will reply in 1 second
TranslateTo],
[// ---------------- Loop.Signal.Test.php8
<<<'TranslateFrom'
// ответит через 2 сек
TranslateFrom,
<<<'TranslateTo'
// will reply in 2 second
TranslateTo],
[// ---------------- Loop.Signal.Test.php8
<<<'TranslateFrom'
// Одновременное ожидание всех задач через Fiber await!
TranslateFrom,
<<<'TranslateTo'
// Waiting for all tasks simultaneously using Fiber await!
TranslateTo],
[// ---------------- Loop.Signal.Test.php8
<<<'TranslateFrom'
// Общее время выполнения будет ~2 секунды вместо 4.5 последовательных.
TranslateFrom,
<<<'TranslateTo'
// The total execution time will be ~2 seconds instead of 4.5 seconds for sequential execution.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
/**
   * Ожидает выполнения всех промисов в массиве.
   * Возвращает массив результатов в тех же ключах.
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Waits for all promises in the array to complete.
   * Returns an array of results with the same keys.
   */
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Если передали обычное значение вместо промиса - оборачиваем его
TranslateFrom,
<<<'TranslateTo'
// If a regular value is passed instead of a promise, we wrap it.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Если упал хотя бы один - реджектим всю пачку (стандартное поведение Promise.all)
TranslateFrom,
<<<'TranslateTo'
// If even one fails, we reject the entire batch (standard Promise.all behavior).
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Команда для безопасного вывода файла в stdout
TranslateFrom,
<<<'TranslateTo'
// Command to safely output a file to stdout
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// stdout дочернего процесса
TranslateFrom,
<<<'TranslateTo'
// stdout of the child process
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Открываем процесс
TranslateFrom,
<<<'TranslateTo'
// Open process
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Делаем потоки неблокирующими
TranslateFrom,
<<<'TranslateTo'
// Making threads non-blocking
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Проверяем готовность данных (вызывать в основном Event Loop)
TranslateFrom,
<<<'TranslateTo'
// Checking data readiness (primarily called within the event loop)
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Проверяем, появились ли данные в пайпе без блокировки (таймаут 0)
TranslateFrom,
<<<'TranslateTo'
// Check if data has appeared in the pipe without blocking (timeout 0).
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Закрываем всё
TranslateFrom,
<<<'TranslateTo'
// We're closing everything down.
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Вызываем колбэк
TranslateFrom,
<<<'TranslateTo'
// We invoke the callback.
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Чтение завершено
TranslateFrom,
<<<'TranslateTo'
// Reading complete
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Процесс еще читает файл
TranslateFrom,
<<<'TranslateTo'
// The process is still reading the file.
TranslateTo],
[// ---------------- FileReadExec.Test.php8
<<<'TranslateFrom'
// Запускаем асинхронное чтение
TranslateFrom,
<<<'TranslateTo'
// Starting asynchronous reading.
TranslateTo],
[// ---------------- FileReadExec.Test.php8
<<<'TranslateFrom'
// Имитация простейшего Event Loop
TranslateFrom,
<<<'TranslateTo'
// Simulating a simple Event Loop
TranslateTo],
[// ---------------- FileReadExec.Test.php8
<<<'TranslateFrom'
// Здесь приложение может обрабатывать HTTP-запросы или тики таймера
TranslateFrom,
<<<'TranslateTo'
// Here, the application can process HTTP requests or timer ticks.
TranslateTo],
[// ---------------- FileReadExec.Test.php8
<<<'TranslateFrom'
// 50мс свободного времени
TranslateFrom,
<<<'TranslateTo'
// 50 ms of free time
TranslateTo],
[// ---------------- Sample.FileRead.ReactPhp.php8
<<<'TranslateFrom'
// Асинхронное чтение файла
TranslateFrom,
<<<'TranslateTo'
// Asynchronous file reading
TranslateTo],
[// ---------------- Sample.FileRead.ReactPhp.php8
<<<'TranslateFrom'
"Файл успешно прочитан! Размер: "
TranslateFrom,
<<<'TranslateTo'
"File successfully read! Size: "
TranslateTo],
[// ---------------- Sample.FileRead.ReactPhp.php8
<<<'TranslateFrom'
" байт.\n"
TranslateFrom,
<<<'TranslateTo'
" bytes.\n"
TranslateTo],
[// ---------------- Sample.FileRead.ReactPhp.php8
<<<'TranslateFrom'
"Ошибка чтения: "
TranslateFrom,
<<<'TranslateTo'
"Read error: "
TranslateTo],
[// ---------------- FileLarge.php
<<<'TranslateFrom'
"Здесь много текста для проверки асинхронности. "
TranslateFrom,
<<<'TranslateTo'
"There is a lot of text here to test for asynchrony. "
TranslateTo],
[// ---------------- FileReadChunk.Test.php8, Promise.Abort.Test.php8
<<<'TranslateFrom'
// 1. Создадим тестовый относительно "большой" файл (для примера)
TranslateFrom,
<<<'TranslateTo'
// 1. Let's create a relatively "large" test file (as an example).
TranslateTo],
[// ---------------- FileReadChunk.Test.php8, FileReadExec.Test.php8
<<<'TranslateFrom'
// 2. Процесс 1: Быстрый таймер, который должен тикать без задержек
TranslateFrom,
<<<'TranslateTo'
// 2. Process 1: A fast timer that must tick without delays.
TranslateTo],
[// ---------------- FileReadChunk.Test.php8, FileReadExec.Test.php8
<<<'TranslateFrom'
// спим 100мс
TranslateFrom,
<<<'TranslateTo'
// Sleep for 100ms.
TranslateTo],
[// ---------------- FileReadChunk.Test.php8, FileReadExec.Test.php8
<<<'TranslateFrom'
// 3. Процесс 2: Асинхронное чтение файла
TranslateFrom,
<<<'TranslateTo'
// 3. Process 2: Asynchronous file reading
TranslateTo],
[// ---------------- FileReadChunk.Test.php8, FileReadExec.Test.php8
<<<'TranslateFrom'
// Вызываем нашу обертку. Код замирает, но Event Loop продолжает крутиться!
TranslateFrom,
<<<'TranslateTo'
// We call our wrapper. The code pauses, but the event loop keeps spinning!
TranslateTo],
[// ---------------- FileReaderChunk.php, FileReaderExec.php
<<<'TranslateFrom'
/**
   * Асинхронно читает весь файл целиком.
   * Возвращает Promise, который резолвится контентом файла.
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Reads the entire file asynchronously.
   * Returns a Promise that resolves with the file content.
   */
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Открываем файл на чтение в бинарном режиме
TranslateFrom,
<<<'TranslateTo'
// Open the file for reading in binary mode.
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Переводим в неблокирующий режим (на некоторых ОС влияет на поведение буферов)
TranslateFrom,
<<<'TranslateTo'
// Switch to non-blocking mode (on some operating systems, this affects buffer behavior).
TranslateTo],
[// ---------------- FileReaderChunk.php, FileReaderExec.php
<<<'TranslateFrom'
// Запускаем рекурсивный процесс чтения через Event Loop
TranslateFrom,
<<<'TranslateTo'
// We initiate a recursive read process via the Event Loop.
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Проверяем, не достигнут ли конец файла
TranslateFrom,
<<<'TranslateTo'
// Check whether the end of the file has been reached.
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Читаем один кусочек
TranslateFrom,
<<<'TranslateTo'
// Let's read one excerpt.
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Вместо того чтобы сразу читать следующий кусок в цикле (что заблокирует поток),
TranslateFrom,
<<<'TranslateTo'
// Instead of immediately reading the next fragment in the loop (which blocks the thread),
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// мы откладываем чтение следующего чанка на следующий "тик" Loop.
TranslateFrom,
<<<'TranslateTo'
// We defer reading the next chunk until the next Loop "tick".
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Таймаут 0 означает: "выполни как можно скорее, но дай сначала подышать другим задачам".
TranslateFrom,
<<<'TranslateTo'
// A timeout of 0 means: "execute as soon as possible, but let other tasks breathe first."
TranslateTo],
[// ---------------- FileReaderExec.php
<<<'TranslateFrom'
// Проверяем, не завершился ли процесс
TranslateFrom,
<<<'TranslateTo'
// We check whether the process has finished.
TranslateTo],
[// ---------------- FileReaderProc.php
<<<'TranslateFrom'
// 1. Указываем путь к файлу
TranslateFrom,
<<<'TranslateTo'
// 1. Specify the path to the file.
TranslateTo],
[// ---------------- FileReaderProc.php
<<<'TranslateFrom'
// Проверяем, существует ли файл и доступен ли для чтения
TranslateFrom,
<<<'TranslateTo'
// Check if the file exists and is readable.
TranslateTo],
[// ---------------- FileReaderProc.php
<<<'TranslateFrom'
// 2. Открываем файл в режиме чтения ('r')
TranslateFrom,
<<<'TranslateTo'
// 2. Open the file in read mode ('r')
TranslateTo],
[// ---------------- FileReaderProc.php
<<<'TranslateFrom'
// 3. Читаем файл построчно до самого конца (EOF)
TranslateFrom,
<<<'TranslateTo'
// 3. Read the file line by line until the end (EOF).
TranslateTo],
[// ---------------- FileReaderProc.php
<<<'TranslateFrom'
// Выводим строку напрямую в консоль
TranslateFrom,
<<<'TranslateTo'
// Output the string directly to the console.
TranslateTo],
[// ---------------- FileReaderProc.php
<<<'TranslateFrom'
// 4. Обязательно закрываем дескриптор файла
TranslateFrom,
<<<'TranslateTo'
// 4. Be sure to close the file descriptor.
TranslateTo],
[// ---------------- Loop.php
<<<'TranslateFrom'
// 2. Проверяем стримы через stream_select
TranslateFrom,
<<<'TranslateTo'
// 2. Checking streams using stream_select
TranslateTo],
[// ---------------- Promise.Any.php8, Promise.Any.Test.php8
<<<'TranslateFrom'
// Сервер 1: сразу падает с ошибкой 500
TranslateFrom,
<<<'TranslateTo'
// Server 1: crashes immediately with a 500 error.
TranslateTo],
[// ---------------- Promise.Any.php8, Promise.Any.Test.php8
<<<'TranslateFrom'
// Сервер 2: успешный, но медленный (ответит через 2 секунды)
TranslateFrom,
<<<'TranslateTo'
// Server 2: successful but slow (responds in 2 seconds)
TranslateTo],
[// ---------------- Promise.Any.php8, Promise.Any.Test.php8
<<<'TranslateFrom'
// Сервер 3: успешный и быстрый (ответит через 0.5 секунды)
TranslateFrom,
<<<'TranslateTo'
// Server 3: successful and fast (responds in 0.5 seconds)
TranslateTo],
[// ---------------- Promise.Any.php8, Promise.Any.Test.php8
<<<'TranslateFrom'
// Ждем первый успешный ответ. Ошибка сервера 1 будет проигнорирована!
TranslateFrom,
<<<'TranslateTo'
// Waiting for the first successful response. Server error 1 will be ignored!
TranslateTo],
[// ---------------- Promise.Any.php8, Promise.Any.Test.php8
<<<'TranslateFrom'
// Выведет данные с сервера 3
TranslateFrom,
<<<'TranslateTo'
// Will output data from server 3
TranslateTo],
[// ---------------- Promise.Any.php8, Promise.Any.Test.php8
<<<'TranslateFrom'
// Запускаем наш Event Loop для обработки обоих кейсов
TranslateFrom,
<<<'TranslateTo'
// We launch our event loop to handle both cases.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
/**
   * Возвращает промис, который завершится так же и с тем же результатом,
   * как и первый завершившийся промис из массива (успех или ошибка).
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Returns a promise that will resolve in the same way and with the same result,
   * just like the first promise from the array to settle (whether successfully or with an error).
   */
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// По спецификации пустой race() навечно остается в состоянии PENDING.
TranslateFrom,
<<<'TranslateTo'
// According to the specification, an empty race() remains in the PENDING state forever.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Но для удобства можно либо выбросить исключение, либо оставить так.
TranslateFrom,
<<<'TranslateTo'
// However, for convenience, you can either throw an exception or leave it as is.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Кто первый вызвал метод у deferred, тот и зафиксировал состояние.
TranslateFrom,
<<<'TranslateTo'
// Whoever called a method on the deferred object first is the one who locked in the state.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Повторные вызовы resolve/reject внутри Deferred просто проигнорируются.
TranslateFrom,
<<<'TranslateTo'
// Subsequent calls to resolve/reject within a Deferred are simply ignored.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
/**
   * Возвращает промис, который выполнится успешно, как только выполнится успешно
   * хотя бы один из промисов в массиве.
   * Если все промисы отклонены, возвращает ошибку AggregateException.
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Returns a promise that resolves as soon as
   * at least one of the promises in the array resolves. 
   * If all promises are rejected, returns an AggregateException.
   */
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
"Promise::any() передан пустой массив."
TranslateFrom,
<<<'TranslateTo'
"An empty array was passed to Promise::any()."
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Обычное значение считается мгновенным успехом
TranslateFrom,
<<<'TranslateTo'
// The standard value is considered an instant success.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Первый же успех резолвит общий промис
TranslateFrom,
<<<'TranslateTo'
// The first success resolves the general promise
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Если упали ВСЕ промисы, отклоняем общий промис
TranslateFrom,
<<<'TranslateTo'
// If all promises have failed, reject the overall promise.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
"Все промисы были отклонены"
TranslateFrom,
<<<'TranslateTo'
"All promises were rejected."
TranslateTo],
[// ---------------- Promise.php, Exception/Aggregate.php
<<<'TranslateFrom'
/**
 * Кастомное исключение для метода Promise::any(),
 * хранящее ошибки всех упавших промисов.
 */
TranslateFrom,
<<<'TranslateTo'
/**
 * A custom exception for the Promise::any() method,
 * storing the errors of all rejected promises.
 */
TranslateTo],
[// ---------------- Promise.Race.php8, Promise.Race.Test.php8
<<<'TranslateFrom'
// Имитируем запрос, который длится 3 секунды
TranslateFrom,
<<<'TranslateTo'
// We simulate a request that takes 3 seconds.
TranslateTo],
[// ---------------- Promise.Race.php8, Promise.Race.Test.php8
<<<'TranslateFrom'
// Создаем промис-таймаут, который упадет через 1.5 секунды
TranslateFrom,
<<<'TranslateTo'
// We create a promise timeout that rejects after 1.5 seconds.
TranslateTo],
[// ---------------- Promise.Race.php8, Promise.Race.Test.php8
<<<'TranslateFrom'
// Запускаем гонку!
TranslateFrom,
<<<'TranslateTo'
// We're kicking off the race!
TranslateTo],
[// ---------------- Promise.Race.php8, Promise.Race.Test.php8
<<<'TranslateFrom'
// Сработает таймаут, так как 1.5с < 3.0с
TranslateFrom,
<<<'TranslateTo'
// The timeout will trigger, since 1.5s < 3.0s.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
/**
   * Ограничивает время выполнения текущего промиса.
   * Если промис не успевает выполниться за $seconds секунд, возвращается отклонённый промис.
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Limits the execution time of the current promise.
   * If the promise does not resolve within $seconds seconds, a rejected promise is returned.
   */
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Создаем промис-таймер, который гарантированно упадет через указанное время.
TranslateFrom,
<<<'TranslateTo'
// We create a promise-based timer that is guaranteed to reject after the specified time.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Для создания таймера используем наш Loop::delay.
TranslateFrom,
<<<'TranslateTo'
// To create a timer, we use our Loop::delay.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Запускаем гонку между текущим промисом ($this) и промисом-таймером.
TranslateFrom,
<<<'TranslateTo'
// We start a race between the current promise ($this) and the timer promise.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Возвращаем результат этой гонки.
TranslateFrom,
<<<'TranslateTo'
// We return the result of this race.
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Имитируем долгий сетевой запрос к API
TranslateFrom,
<<<'TranslateTo'
// Simulating a long-running network request to the API.
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Ответ придет только через 3 секунды
TranslateFrom,
<<<'TranslateTo'
// The answer will arrive only after 3 seconds.
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Процесс 1: Запрос, который НЕ уложится в таймаут
TranslateFrom,
<<<'TranslateTo'
// Process 1: A request that will NOT time out.
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Просто вызываем метод цепочкой прямо у промиса!
TranslateFrom,
<<<'TranslateTo'
// We simply call the method in a chain directly on the promise!
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Сработает этот блок, так как 1.0с < 3.0с
TranslateFrom,
<<<'TranslateTo'
// This block will execute, since 1.0s < 3.0s.
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Процесс 2: Запрос, который УЛОЖИТСЯ в таймаут (дали ему 4 секунды)
TranslateFrom,
<<<'TranslateTo'
// Process 2: A request that fits within the timeout (it was given 4 seconds)
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Немного подождем, чтобы логи не перемешивались слишком хаотично
TranslateFrom,
<<<'TranslateTo'
// Let's wait a bit so the logs don't get mixed up too chaotically.
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Можно кастомизировать текст ошибки при желании
TranslateFrom,
<<<'TranslateTo'
// You can customize the error text if desired.
TranslateTo],
[// ---------------- Promise.TimeOut.Test.php8
<<<'TranslateFrom'
// Запуск цикла событий
TranslateFrom,
<<<'TranslateTo'
// Starting the event loop
TranslateTo],
[// ---------------- Promise.AllSettled.Test.php8
<<<'TranslateFrom'
// Успешно вернет баланс
TranslateFrom,
<<<'TranslateTo'
// It will successfully restore balance.
TranslateTo],
[// ---------------- Promise.AllSettled.Test.php8
<<<'TranslateFrom'
// Упадет
TranslateFrom,
<<<'TranslateTo'
// It will fall
TranslateTo],
[// ---------------- Promise.AllSettled.Test.php8
<<<'TranslateFrom'
// Метод allSettled() никогда не выбросит исключение в await!
TranslateFrom,
<<<'TranslateTo'
// The allSettled() method will never throw an exception when awaited!
TranslateTo],
[// ---------------- Promise.AllSettled.Test.php8
<<<'TranslateFrom'
// В поле 'reason' лежит Throwable или строка ошибки
TranslateFrom,
<<<'TranslateTo'
// The 'reason' field contains a Throwable or an error string.
TranslateTo],
[// ---------------- Promise.AllSettled.Test.php8
<<<'TranslateFrom'
// Запускаем Event Loop
TranslateFrom,
<<<'TranslateTo'
// Starting the Event Loop
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
/**
   * Ожидает завершения всех промисов (успешного или с ошибкой).
   * Возвращает массив структур: 
   * ['status' => 'fulfilled', 'value' => $value] или ['status' => 'rejected', 'reason' => $reason]
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Waits for all promises to complete (successfully or with an error).
   * Returns an array of structures:
   * ['status' => 'fulfilled', 'value' => $value] или ['status' => 'rejected', 'reason' => $reason]
   */
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Если передали обычное значение - это моментальный успех
TranslateFrom,
<<<'TranslateTo'
// If you convey the ordinary meaning, it's an instant success.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Нам важен сам факт завершения, поэтому логика сбора идентична для обоих колбэков
TranslateFrom,
<<<'TranslateTo'
// The fact of completion itself is what matters to us, so the collection logic is identical for both callbacks.
TranslateTo],
[// ---------------- AbortController.php
<<<'TranslateFrom'
/**
 * Управляющий объект для отмены задач
 */
TranslateFrom,
<<<'TranslateTo'
/**
 * Task cancellation manager
 */
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
/**
 * Токен отмены, передаваемый внутрь асинхронных функций
 */
TranslateFrom,
<<<'TranslateTo'
/**
 * Cancellation token passed into asynchronous functions
 */
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
/**
   * Позволяет асинхронной функции подписаться на событие отмены
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Allows an asynchronous function to subscribe to a cancellation event.
   */
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
/**
   * Внутренний метод, вызываемый только из AbortController
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Internal method called only from AbortController
   */
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
"Операция была отменена."
TranslateFrom,
<<<'TranslateTo'
"The operation was cancelled."
TranslateTo],
[// ---------------- AbortSignal.Test.php8
<<<'TranslateFrom'
// Подготовим файл
TranslateFrom,
<<<'TranslateTo'
// We will prepare the file.
TranslateTo],
[// ---------------- AbortSignal.Test.php8
<<<'TranslateFrom'
// 1. Создаем контроллер отмены
TranslateFrom,
<<<'TranslateTo'
// 1. Create a cancellation controller.
TranslateTo],
[// ---------------- AbortSignal.Test.php8
<<<'TranslateFrom'
// 2. Имитируем внешнее событие: через 20 миллисекунд отменяем операцию
TranslateFrom,
<<<'TranslateTo'
// 2. Simulate an external event: cancel the operation after 20 milliseconds.
TranslateTo],
[// ---------------- AbortSignal.Test.php8
<<<'TranslateFrom'
// Передаем сигнал и в саму операцию, и в await
TranslateFrom,
<<<'TranslateTo'
// We pass the signal both to the operation itself and to the await.
TranslateTo],
[// ---------------- AbortSignal.Test.php8
<<<'TranslateFrom'
// Ловим именно наше кастомное исключение отмены
TranslateFrom,
<<<'TranslateTo'
// We catch specifically our custom cancellation exception.
TranslateTo],
[// ---------------- Exception/Abort.php
<<<'TranslateFrom'
/**
 * Исключение, выбрасываемое при отмене операции
 */
TranslateFrom,
<<<'TranslateTo'
/**
 * Exception thrown when an operation is cancelled
 */
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Флаг для внутренней остановки рекурсии
TranslateFrom,
<<<'TranslateTo'
// Flag for internal recursion termination
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Если была отмена, выходим
TranslateFrom,
<<<'TranslateTo'
// If there was a cancellation, we exit.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// 1. Проверяем, не отменено ли всё еще до начала ожидания
TranslateFrom,
<<<'TranslateTo'
// 1. Check whether cancellation has already occurred before the wait begins.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// 2. Если передан сигнал, подписываемся на него
TranslateFrom,
<<<'TranslateTo'
// 2. If a signal is transmitted, we subscribe to it.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Если файбер все еще на паузе, бросаем в него исключение отмены
TranslateFrom,
<<<'TranslateTo'
// If the fiber is still paused, throw a cancellation exception into it.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Колбэк, который вызывается при отмене для очистки ресурсов (стримов, таймеров)
TranslateFrom,
<<<'TranslateTo'
// A callback invoked upon cancellation to clean up resources (streams, timers).
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// Метод для установки колбэка очистки (используется внутри AsyncFile/httpGet)
TranslateFrom,
<<<'TranslateTo'
// Method for setting a cleanup callback (used within AsyncFile/httpGet)
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
/**
   * Принудительная отмена конкретного промиса
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Forced cancellation of a specific promise
   */
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
"Промис был отменен."
TranslateFrom,
<<<'TranslateTo'
"The promise was cancelled."
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// 1. Вызываем очистку системных ресурсов, если она была задана
TranslateFrom,
<<<'TranslateTo'
// 1. Invoke system resource cleanup if it has been specified.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
// 2. Отклоняем промис с ошибкой AbortException
TranslateFrom,
<<<'TranslateTo'
// 2. Reject the promise with an AbortException.
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
/**
 * Токен отмены, передаваемый внутрь асинхронных функций или шлёт событие отмены промисам.
 */
TranslateFrom,
<<<'TranslateTo'
/**
 * A cancellation token passed into asynchronous functions or used to send a cancellation event to promises.
 */
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
// Храним связанные промисы
TranslateFrom,
<<<'TranslateTo'
// We store the associated promises.
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
/**
   * Позволяет старой асинхронной функции подписаться на событие отмены
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Allows an old asynchronous function to subscribe to a cancellation event.
   */
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
/**
   * Привязывает промис к этому сигналу.
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Binds the promise to this signal.
   */
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
// 1. Оповещаем обычных слушателей
TranslateFrom,
<<<'TranslateTo'
// 1. Notifying general listeners
TranslateTo],
[// ---------------- AbortSignal.php
<<<'TranslateFrom'
// 2. Раздаем сигнал отмены всем привязанным промисам!
TranslateFrom,
<<<'TranslateTo'
// 2. Broadcast the cancellation signal to all linked promises!
TranslateTo],
[// ---------------- FileReaderChunk.php
<<<'TranslateFrom'
// Регистрируем логику отмены прямо в промисе!
TranslateFrom,
<<<'TranslateTo'
// We register the cancellation logic right inside the promise!
TranslateTo],
[// ---------------- Promise.Abort.Test.php8
<<<'TranslateFrom'
// Подготовим тестовые файлы
TranslateFrom,
<<<'TranslateTo'
// We will prepare the test files.
TranslateTo],
[// ---------------- Promise.Abort.Test.php8
<<<'TranslateFrom'
// Создаем промисы чтения файлов
TranslateFrom,
<<<'TranslateTo'
// Creating file-reading promises
TranslateTo],
[// ---------------- Promise.Abort.Test.php8
<<<'TranslateFrom'
// Навешиваем их на один AbortController / AbortSignal
TranslateFrom,
<<<'TranslateTo'
// We attach them to a single AbortController / AbortSignal.
TranslateTo],
[// ---------------- Promise.Abort.Test.php8
<<<'TranslateFrom'
// Имитируем отмену всей группы задач через 15 миллисекунд
TranslateFrom,
<<<'TranslateTo'
// We simulate the cancellation of the entire task group after 15 milliseconds.
TranslateTo],
[// ---------------- Promise.Abort.Test.php8
<<<'TranslateFrom'
// Функция для безопасного await
TranslateFrom,
<<<'TranslateTo'
// Function for safe await
TranslateTo],
[// ---------------- Promise.Abort.Test.php8
<<<'TranslateFrom'
// Запускаем ожидание
TranslateFrom,
<<<'TranslateTo'
// Starting the wait process.
TranslateTo],
[// ---------------- Promise.Abort.Test.php8
<<<'TranslateFrom'
// Создаем промисы чтения файлов и навешиваем их на один AbortController / AbortSignal
TranslateFrom,
<<<'TranslateTo'
// We create file-reading promises and attach them to a single AbortController / AbortSignal.
TranslateTo],
[// ---------------- Promise.php
<<<'TranslateFrom'
/**
   * Привязывает этот промис к указанному сигналу отмены.
   * Возвращает текущий промис для чейнинга (Fluent API).
   */
TranslateFrom,
<<<'TranslateTo'
/**
   * Binds this promise to the specified cancellation signal.
   * Returns the current promise for chaining (Fluent API).
   */
TranslateTo],
];