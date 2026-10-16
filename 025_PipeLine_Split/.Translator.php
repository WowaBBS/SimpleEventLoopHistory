<? Return [//PHPFormatter: Translate file
[// ---------------- //Comment (Promise.Chain.Test.php8:4)
'From' => 'Чейнинг (Цепочки вызовов)',
'To'   => 'Chaining (Call chains)'],
[// ---------------- \Promise//Comment (Promise.php:2)
<<<'TranslateFrom'
Promise::all([...]) - ждет выполнения всех промисов. Если хоть один упал - падает вся цепочка.
Promise::race([...]) - возвращает результат первого завершившегося промиса.
Promise::resolve($value) / Promise::reject($reason) - для быстрого создания уже выполненных или отклоненных промисов.
TranslateFrom,
<<<'TranslateTo'
Promise::all([...]) - It waits for all promises to resolve. If even one fails, the entire chain fails.
Promise::race([...]) - returns the result of the first promise to settle.
Promise::resolve($value) / Promise::reject($reason) - for quickly creating promises that have already been fulfilled or rejected.
TranslateTo],
[// ---------------- \Promise::Handler()//Comment (Promise.php:54)
'From' => 'Если вернулся другой промис, ждем его',
'To'   => 'If another promise is returned, we wait for it.'],
[// ---------------- //Comment (Promise.Simple.Test.php8:4)
'From' => 'Пример интерфейса, к которому стоит стремиться',
'To'   => 'An example of an interface to aim for'],
[// ---------------- //Comment (Promise.Simple.Test.php8:15)
'From' => 'Где-то в асинхронном коде:',
'To'   => 'Somewhere in the asynchronous code:'],
[// ---------------- \fetchUserData()//Comment (Fiber.Test.php8:15)
'From' => 'Функция, имитирующая асинхронный запрос (например, к API)',
'To'   => 'A function simulating an asynchronous request (e.g., to an API)'],
[// ---------------- \fetchUserData()//Comment (Fiber.Test.php8:24)
<<<'TranslateFrom'
Имитируем некую асинхронную операцию (в реальности тут будет ваш Event Loop)
Для примера просто резолвим через секунду
TranslateFrom,
<<<'TranslateTo'
We simulate an asynchronous operation (in reality, this is where your Event Loop would be).
For example, we just resolve after a second.
TranslateTo],
[// ---------------- //Comment (Fiber.Test.php8:32)
'From' => 'Запускаем асинхронный контекст через Fiber',
'To'   => 'Launching an asynchronous context via a fiber.'],
[// ---------------- =>fn()//Comment (Fiber.Test.php8:37)
'From' => 'Магия: код замирает здесь, пока промис не выполнится',
'To'   => 'The magic: the code pauses here until the promise resolves.'],
[// ---------------- =>fn()//Comment (Fiber.Test.php8:42)
<<<'TranslateFrom'
Можно делать цепочки зависимых запросов друг за другом:
$orders = fetchOrders($user['id'])->await();
TranslateFrom,
<<<'TranslateTo'
You can create chains of dependent requests, one after another:
$orders = fetchOrders($user['id'])->await();
TranslateTo],
[// ---------------- //Comment (Fiber.Test.php8:51)
'From' => 'Запускаем файбер (он дойдет до Fiber::suspend() внутри await() и вернет управление сюда)',
'To'   => 'We start the fiber (it will proceed to Fiber::suspend() inside await() and return control here).'],
[// ---------------- //Comment (Fiber.Test.php8:54)
<<<'TranslateFrom'
Имитируем работу Event Loop, который "тикает" и продвигает асинхронные задачи
В реальном приложении это делает класс Loop/EventLoop
TranslateFrom,
<<<'TranslateTo'
Simulate the operation of the Event Loop, which "ticks" and advances asynchronous tasks.
In a real application, the Loop/EventLoop class handles this.
TranslateTo],
[// ---------------- //Comment (Fiber.Test.php8:57)
'From' => 'Вызываем тики, чтобы сработал наш асинхронный код',
'To'   => 'We trigger ticks to make our asynchronous code execute.'],
[// ---------------- \Promise::await()//Comment (Promise.php:131)
'From' => 'Если промис уже выполнен, сразу возвращаем значение (без паузы)',
'To'   => 'If the promise has already been fulfilled, we return the value immediately (without a pause).'],
[// ---------------- \Promise::await()//Comment (Promise.php:135)
<<<'TranslateFrom'
Проверяем, находимся ли мы внутри файбера
TranslateFrom,
<<<'TranslateTo'
Let's check if we are inside the fiber
TranslateTo],
[// ---------------- \Promise::await()//String (Promise.php:138)
'From' => '"Метод await() можно вызывать только внутри Fiber."',
'To'   => '"The await() method can only be called within a Fiber."'],
[// ---------------- \Promise::await()//Comment (Promise.php:140)
'From' => 'Подписываемся на этот же промис',
'To'   => 'We subscribe to the same promise'],
[// ---------------- \Promise::await()=>fn()//Comment (Promise.php:142)
'From' => 'Когда выполнится - возобновляем файбер и передаем результат',
'To'   => 'Once it completes, we resume the fiber and pass on the result.'],
[// ---------------- \Promise::await()=>fn()//Comment (Promise.php:144)
'From' => 'Если ошибка - возобновляем файбер, бросая в него исключение',
'To'   => 'If an error occurs, we resume the fiber by throwing an exception into it.'],
[// ---------------- \Promise::await()//Comment (Promise.php:152)
<<<'TranslateFrom'
Приостанавливаем файбер. Управление возвращается в основной поток (Event Loop)
Когда вызовется resume() или throw(), код продолжится со следующей строки
TranslateFrom,
<<<'TranslateTo'
Suspend the fiber. Control returns to the main thread (Event Loop).
When resume() or throw() is called, execution will continue from the next line.
TranslateTo],
[// ---------------- \httpGetAsync()//Comment (Http.php:10)
'From' => 'Открываем НЕБЛОКИРУЮЩИЙ сокет',
'To'   => 'Opening a non-blocking socket.'],
[// ---------------- \httpGetAsync()//Comment (Http.php:14)
'From' => 'Записываем HTTP-запрос (для простоты пишем сразу, в идеале тоже асинхронно через write stream)',
'To'   => 'We write the HTTP request (for simplicity, we write it directly, though ideally this should also be done asynchronously using a write stream).'],
[// ---------------- \httpGetAsync()//Comment (Http.php:19)
'From' => 'Регистрируем сокет в нашем Event Loop',
'To'   => 'We register the socket in our event loop.'],
[// ---------------- \httpGetAsync()=>fn()//Comment (Http.php:24)
'From' => 'Поток закрылся (данные кончились)',
'To'   => 'The stream has closed (data exhausted).'],
[// ---------------- \FileReaderExec::CheckFinish()//Comment (FileReaderExec.php:112:114), \httpGetAsync()=>fn()//Comment (Http.php:27)
'From' => 'Успешно отдаем ответ',
'To'   => 'Successfully sending the response'],
[// ---------------- //Comment (Loop.Async.Test.php8:4)
'From' => '1. Запускаем первый асинхронный процесс',
'To'   => '1. Launch the first asynchronous process.'],
[// ---------------- =>fn()//Comment (Loop.Async.Test.php8:8)
<<<'TranslateFrom'
Магия Fibers: await() ставит этот файбер на паузу, 
пока Loop качает данные из сокета
TranslateFrom,
<<<'TranslateTo'
Fibers Magic: await() pauses this fiber,
while the loop is downloading data from the socket
TranslateTo],
[// ---------------- //Comment (Loop.Async.Test.php8:16)
'From' => '2. Параллельно запускаем второй процесс',
'To'   => '2. We launch a second process in parallel.'],
[// ---------------- =>fn()//Comment (Loop.Async.Test.php8:20)
'From' => 'Спим без блокирования всего PHP-скрипта',
'To'   => 'Sleep without blocking the entire PHP script.'],
[// ---------------- //Comment (Loop.Async.Test.php8:26)
'From' => '3. Запускаем бесконечный цикл событий, который крутит все эти процессы',
'To'   => '3. We start an infinite event loop that drives all these processes.'],
[// ---------------- \Loop::delay()//Comment (Loop.php:10)
'From' => 'Добавить отложенную задачу (таймер)',
'To'   => 'Add a scheduled task (timer)'],
[// ---------------- \Loop::addReadStream()//Comment (Loop.php:19)
'From' => 'Подписаться на появление данных в сокете/потоке',
'To'   => 'Subscribe to data availability in a socket/stream'],
[// ---------------- \Loop::removeReadStream()//Comment (Loop.php:29)
'From' => 'Отписаться от потока',
'To'   => 'Unsubscribe from stream'],
[// UNUSED ----------------
'From' => 'Запустить цикл',
'To'   => 'Start the loop'],
[// ---------------- \Loop::run()//Comment (Loop.php:113)
'From' => '1. Обрабатываем таймеры, у которых подошло время',
'To'   => '1. Process timers that have expired.'],
[// UNUSED ----------------
'From' => '2. Проверяем сокеты через stream_select',
'To'   => '2. Check sockets using stream_select.'],
[// ---------------- \Loop::run()//Comment (Loop.php:129)
'From' => 'Таймаут для select: берем время до ближайшего таймера или 1 секунду',
'To'   => 'Timeout for select: use the time until the nearest timer or 1 second.'],
[// NEW ---------------- \Loop::run()//Comment (Loop.php:140)
<<<'TranslateFrom'
Ждем системного уведомления о готовности потоков
Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
поэтому глушим ее через @ и проверяем статус
TranslateFrom,
<<<'TranslateTo'
Ждем системного уведомления о готовности потоков
Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
поэтому глушим ее через @ и проверяем статус
TranslateTo],
[// UNUSED ----------------
'From' => 'Ждем системного уведомления о готовности потоков',
'To'   => 'We are awaiting a system notification regarding the readiness of the streams.'],
[// UNUSED ----------------
'From' => 'Если сокетов нет, просто спим микросекунду, чтобы не грузить CPU на 100%',
'To'   => 'If there are no sockets, we simply sleep for a microsecond to avoid loading the CPU at 100%.'],
[// ---------------- \Loop::async()//Comment (Loop.php:157)
'From' => 'Удобный хелпер для запуска кода в изолированном файбере',
'To'   => 'A handy helper for running code in an isolated fiber'],
[// ---------------- \Loop::async()//Comment (Loop.php:161)
'From' => 'Запуск файбера. Если внутри будет Fiber::suspend(), управление вернется сюда',
'To'   => 'Starting the fiber. If Fiber::suspend() is called inside, control will return here.'],
[// UNUSED ----------------
'From' => '... (предыдущие свойства: state, value, handlers)',
'To'   => '... (previous properties: state, value, handlers)'],
[// UNUSED ----------------
'From' => '... (методы delay, addReadStream, removeReadStream остаются прежними)',
'To'   => '... (методы delay, addReadStream, removeReadStream остаются прежними)'],
[// ---------------- \Loop::addSignal()//Doc (Loop.php:36)
'From' => 'Подписка на системный сигнал (например, SIGINT / Ctrl+C)',
'To'   => 'Subscribing to a system signal (e.g., SIGINT / Ctrl+C)'],
[// ---------------- \Loop::stop()//Doc (Loop.php:45)
'From' => 'Остановка цикла событий',
'To'   => 'Stopping the event loop'],
[// ---------------- \Loop::initSignalHandling()//Doc (Loop.php:53)
'From' => 'Инициализация обработчиков под текущую ОС',
'To'   => 'Initializing handlers for the current OS'],
[// ---------------- \Loop::initSignalHandling()//Comment (Loop.php:61)
'From' => '--- ВАРИАНТ ДЛЯ WINDOWS ---',
'To'   => '--- WINDOWS VERSION ---'],
[// ---------------- \Loop::initSignalHandling()=>fn()//Comment (Loop.php:64)
'From' => 'PHP_WINDOWS_EVENT_CTRL_C - это константа нажатия Ctrl+C под Windows',
'To'   => 'PHP_WINDOWS_EVENT_CTRL_C is the constant for the Ctrl+C key press on Windows.'],
[// ---------------- \Loop::initSignalHandling()=>fn()//Comment (Loop.php:67)
'From' => 'Имитируем SIGINT (2) для кроссплатформенности',
'To'   => 'Simulating SIGINT (2) for cross-platform compatibility'],
[// ---------------- \Loop::initSignalHandling()=>fn()//Comment (Loop.php:68)
'From' => 'Говорим ОС, что событие обработано',
'To'   => 'We tell the OS that the event has been processed.'],
[// ---------------- \Loop::initSignalHandling()//Comment (Loop.php:74)
'From' => '--- ВАРИАНТ ДЛЯ UNIX (Linux, macOS) ---',
'To'   => '--- UNIX VERSION (Linux, macOS) ---'],
[// ---------------- \Loop::initSignalHandling()//Comment (Loop.php:76)
'From' => 'Регистрируем сигналы SIGINT (Ctrl+C) и SIGTERM (команда kill)',
'To'   => 'We register SIGINT (Ctrl+C) and SIGTERM (kill command) signals.'],
[// ---------------- \Loop::triggerSignal()//Doc (Loop.php:85)
'From' => 'Вызов всех зарегистрированных колбэков на этот сигнал',
'To'   => 'Invoke all registered callbacks for this signal.'],
[// ---------------- \Loop::run()//Doc (Loop.php:95)
'From' => 'Главный запуск цикла',
'To'   => 'Main cycle launch'],
[// ---------------- \Loop::run()//Comment (Loop.php:104)
'From' => 'КРИТИЧНО ДЛЯ UNIX: Проверяем, не пришли ли сигналы PCNTL',
'To'   => 'CRITICAL FOR UNIX: Check for PCNTL signals.'],
[// ---------------- \Loop::run()//Comment (Loop.php:108)
'From' => 'Если во время обработки сигнала вызвали Loop::stop(), выходим',
'To'   => 'If Loop::stop() was called during signal processing, exit.'],
[// UNUSED ----------------
'From' => '1. Таймеры',
'To'   => '1. Timers'],
[// UNUSED ----------------
'From' => '2. Стримы через stream_select',
'To'   => '2. Streams via stream_select'],
[// UNUSED ----------------
<<<'TranslateFrom'
Ошибка stream_select прерывает функцию при получении системного сигнала на Linux,
поэтому глушим ее через @ и проверяем статус
TranslateFrom,
<<<'TranslateTo'
Stream_select error aborts function when receiving system signal on Linux,
so we silence it using @ and check the status
TranslateTo],
[// ---------------- \Loop::run()//Comment (Loop.php:153)
'From' => 'Маленький сон, если задач нет, чтобы не перегружать CPU',
'To'   => 'A short sleep if there are no tasks, to avoid overloading the CPU.'],
[// ---------------- //Comment (Loop.Signal.Test.php8:4)
'From' => 'Подписываемся на сигнал SIGINT (Константа 2 - это универсальный SIGINT)',
'To'   => 'We subscribe to the SIGINT signal (Constant 2 is the universal SIGINT).'],
[// ---------------- =>fn()//Comment (Loop.Signal.Test.php8:13)
'From' => 'Создаем массив асинхронных задач',
'To'   => 'Creating an array of asynchronous tasks'],
[// ---------------- =>fn()//Comment (Loop.Signal.Test.php8:16)
'From' => 'ответит через 1 сек',
'To'   => 'will reply in 1 second'],
[// ---------------- =>fn()//Comment (Loop.Signal.Test.php8:17)
'From' => 'ответит через 2 сек',
'To'   => 'will reply in 2 second'],
[// ---------------- =>fn()//Comment (Loop.Signal.Test.php8:25)
<<<'TranslateFrom'
Одновременное ожидание всех задач через Fiber await!
Общее время выполнения будет ~2 секунды вместо 4.5 последовательных.
TranslateFrom,
<<<'TranslateTo'
Waiting for all tasks simultaneously using Fiber await!
The total execution time will be ~2 seconds instead of 4.5 seconds for sequential execution.
TranslateTo],
[// ---------------- \Promise::all()//Doc (Promise.php:157)
<<<'TranslateFrom'
Ожидает выполнения всех промисов в массиве.
Возвращает массив результатов в тех же ключах.
TranslateFrom,
<<<'TranslateTo'
Waits for all promises in the array to complete.
Returns an array of results with the same keys.
TranslateTo],
[// ---------------- \Promise::all()//Comment (Promise.php:175)
'From' => 'Если передали обычное значение вместо промиса - оборачиваем его',
'To'   => 'If a regular value is passed instead of a promise, we wrap it.'],
[// ---------------- \Promise::all()=>fn()//Comment (Promise.php:190)
'From' => 'Если упал хотя бы один - реджектим всю пачку (стандартное поведение Promise.all)',
'To'   => 'If even one fails, we reject the entire batch (standard Promise.all behavior).'],
[// ---------------- \FileReaderExec::__construct()//Comment (FileReaderExec.php:32)
'From' => 'Команда для безопасного вывода файла в stdout',
'To'   => 'Command to safely output a file to stdout'],
[// ---------------- \FileReaderExec::__construct()//Comment (FileReaderExec.php:37)
'From' => 'stdout дочернего процесса',
'To'   => 'stdout of the child process'],
[// ---------------- \FileReaderExec::__construct()//Comment (FileReaderExec.php:41)
'From' => 'Открываем процесс',
'To'   => 'Open process'],
[// ---------------- \FileReaderExec::__construct()//Comment (FileReaderExec.php:46)
'From' => 'Делаем потоки неблокирующими',
'To'   => 'Making threads non-blocking'],
[// ---------------- \FileReaderExec::tick()//Comment (FileReaderExec.php:117)
'From' => 'Проверяем готовность данных (вызывать в основном Event Loop)',
'To'   => 'Checking data readiness (primarily called within the event loop)'],
[// ---------------- \FileReaderExec::tick()//Comment (FileReaderExec.php:126)
'From' => 'Проверяем, появились ли данные в пайпе без блокировки (таймаут 0)',
'To'   => 'Check if data has appeared in the pipe without blocking (timeout 0).'],
[// ---------------- \FileReaderExec::tick()//Comment (FileReaderExec.php:132)
<<<'TranslateFrom'
Закрываем всё
TranslateFrom,
<<<'TranslateTo'
We're closing everything down.
TranslateTo],
[// ---------------- \FileReaderExec::tick()//Comment (FileReaderExec.php:138)
'From' => 'Вызываем колбэк',
'To'   => 'We invoke the callback.'],
[// ---------------- \FileReaderExec::tick()//Comment (FileReaderExec.php:140)
'From' => 'Чтение завершено',
'To'   => 'Reading complete'],
[// ---------------- \FileReaderExec::tick()//Comment (FileReaderExec.php:143)
'From' => 'Процесс еще читает файл',
'To'   => 'The process is still reading the file.'],
[// UNUSED ----------------
<<<'TranslateFrom'
 Запускаем асинхронное чтение
$FileName='large_file.log';
$FileName=__FILE__;
TranslateFrom,
<<<'TranslateTo'
 Запускаем асинхронное чтение
$FileName='large_file.log';
$FileName=__FILE__;
TranslateTo],
[// UNUSED ----------------
<<<'TranslateFrom'
 Запускаем асинхронное чтение
$FileName='large_file.log';
TranslateFrom,
<<<'TranslateTo'
 Starting asynchronous reading.
$FileName='large_file.log';
TranslateTo],
[// UNUSED ----------------
'From' => 'Имитация простейшего Event Loop',
'To'   => 'Simulating a simple Event Loop'],
[// UNUSED ----------------
'From' => 'Здесь приложение может обрабатывать HTTP-запросы или тики таймера',
'To'   => 'Here, the application can process HTTP requests or timer ticks.'],
[// UNUSED ----------------
'From' => '50мс свободного времени',
'To'   => '50 ms of free time'],
[// UNUSED ----------------
'From' => 'Асинхронное чтение файла',
'To'   => 'Asynchronous file reading'],
[// UNUSED ----------------
'From' => '"Файл успешно прочитан! Размер: "',
'To'   => '"File successfully read! Size: "'],
[// UNUSED ----------------
<<<'TranslateFrom'
" байт.\n"
TranslateFrom,
<<<'TranslateTo'
" bytes.\n"
TranslateTo],
[// UNUSED ----------------
'From' => '"Ошибка чтения: "',
'To'   => '"Read error: "'],
[// ---------------- //String (FileLarge.php:6)
'From' => '"Здесь много текста для проверки асинхронности. "',
'To'   => '"There is a lot of text here to test for asynchrony. "'],
[// ---------------- //Comment (FileReadChunk.Test.php8:4, Promise.Abort.Test.php8:4)
<<<'TranslateFrom'
1. Создадим тестовый относительно "большой" файл (для примера)
TranslateFrom,
<<<'TranslateTo'
1. Let's create a relatively "large" test file (as an example).
TranslateTo],
[// ---------------- //Comment (FileReadChunk.Test.php8:7, FileReadExec.Test.php8:6)
'From' => '2. Процесс 1: Быстрый таймер, который должен тикать без задержек',
'To'   => '2. Process 1: A fast timer that must tick without delays.'],
[// ---------------- =>fn()//Comment (FileReadChunk.Test.php8:12, FileReadExec.Test.php8:11)
'From' => 'спим 100мс',
'To'   => 'Sleep for 100ms.'],
[// ---------------- //Comment (FileReadChunk.Test.php8:16, FileReadExec.Test.php8:15)
'From' => '3. Процесс 2: Асинхронное чтение файла',
'To'   => '3. Process 2: Asynchronous file reading'],
[// ---------------- =>fn()//Comment (FileReadChunk.Test.php8:24, FileReadExec.Test.php8:23)
'From' => 'Вызываем нашу обертку. Код замирает, но Event Loop продолжает крутиться!',
'To'   => 'We call our wrapper. The code pauses, but the event loop keeps spinning!'],
[// ---------------- \FileReaderChunk::readAll()//Doc (FileReaderChunk.php:5), \FileReaderExec::readAll()//Doc (FileReaderExec.php:9)
<<<'TranslateFrom'
Асинхронно читает весь файл целиком.
Возвращает Promise, который резолвится контентом файла.
TranslateFrom,
<<<'TranslateTo'
Reads the entire file asynchronously.
Returns a Promise that resolves with the file content.
TranslateTo],
[// ---------------- \FileReaderChunk::readAll()//Comment (FileReaderChunk.php:20)
'From' => 'Открываем файл на чтение в бинарном режиме',
'To'   => 'Open the file for reading in binary mode.'],
[// ---------------- \FileReaderChunk::readAll()//Comment (FileReaderChunk.php:28)
'From' => 'Переводим в неблокирующий режим (на некоторых ОС влияет на поведение буферов)',
'To'   => 'Switch to non-blocking mode (on some operating systems, this affects buffer behavior).'],
[// ---------------- \FileReaderChunk::readAll()//Comment (FileReaderChunk.php:42), \FileReaderExec::readAll()//Comment (FileReaderExec.php:15)
'From' => 'Запускаем рекурсивный процесс чтения через Event Loop',
'To'   => 'We initiate a recursive read process via the Event Loop.'],
[// ---------------- \FileReaderChunk::readChunk()//Comment (FileReaderChunk.php:52)
'From' => 'Проверяем, не достигнут ли конец файла',
'To'   => 'Check whether the end of the file has been reached.'],
[// ---------------- \FileReaderChunk::readChunk()//Comment (FileReaderChunk.php:60)
<<<'TranslateFrom'
Читаем один кусочек
TranslateFrom,
<<<'TranslateTo'
Let's read one excerpt.
TranslateTo],
[// ---------------- \FileReaderChunk::readChunk()//Comment (FileReaderChunk.php:72)
<<<'TranslateFrom'
Вместо того чтобы сразу читать следующий кусок в цикле (что заблокирует поток),
мы откладываем чтение следующего чанка на следующий "тик" Loop.
Таймаут 0 означает: "выполни как можно скорее, но дай сначала подышать другим задачам".
TranslateFrom,
<<<'TranslateTo'
Instead of immediately reading the next fragment in the loop (which blocks the thread),
defer reading the next chunk until the next Loop "tick".
A timeout of 0 means: "execute as soon as possible, but let other tasks breathe first."
TranslateTo],
[// ---------------- \FileReaderExec::CheckFinish()//Comment (FileReaderExec.php:95)
'From' => 'Проверяем, не завершился ли процесс',
'To'   => 'We check whether the process has finished.'],
[// ---------------- //Comment (FileReaderProc.php:3)
'From' => '1. Указываем путь к файлу',
'To'   => '1. Specify the path to the file.'],
[// ---------------- //Comment (FileReaderProc.php:9)
'From' => 'Проверяем, существует ли файл и доступен ли для чтения',
'To'   => 'Check if the file exists and is readable.'],
[// ---------------- //Comment (FileReaderProc.php:16)
<<<'TranslateFrom'
2. Открываем файл в режиме чтения ('r')
TranslateFrom,
<<<'TranslateTo'
2. Open the file in read mode ('r')
TranslateTo],
[// ---------------- //Comment (FileReaderProc.php:25)
'From' => '3. Читаем файл построчно до самого конца (EOF)',
'To'   => '3. Read the file line by line until the end (EOF).'],
[// ---------------- //Comment (FileReaderProc.php:32:40)
'From' => 'Выводим строку напрямую в консоль',
'To'   => 'Output the string directly to the console.'],
[// ---------------- //Comment (FileReaderProc.php:46)
'From' => '4. Обязательно закрываем дескриптор файла',
'To'   => '4. Be sure to close the file descriptor.'],
[// ---------------- \Loop::run()//Comment (Loop.php:122)
'From' => '2. Проверяем стримы через stream_select',
'To'   => '2. Checking streams using stream_select'],
[// ---------------- =>fn()//Comment (Promise.Any.Test.php8:8)
'From' => 'Сервер 1: сразу падает с ошибкой 500',
'To'   => 'Server 1: crashes immediately with a 500 error.'],
[// ---------------- =>fn()//Comment (Promise.Any.Test.php8:11)
'From' => 'Сервер 2: успешный, но медленный (ответит через 2 секунды)',
'To'   => 'Server 2: successful but slow (responds in 2 seconds)'],
[// ---------------- =>fn()//Comment (Promise.Any.Test.php8:14)
'From' => 'Сервер 3: успешный и быстрый (ответит через 0.5 секунды)',
'To'   => 'Server 3: successful and fast (responds in 0.5 seconds)'],
[// ---------------- =>fn()//Comment (Promise.Any.Test.php8:20)
'From' => 'Ждем первый успешный ответ. Ошибка сервера 1 будет проигнорирована!',
'To'   => 'Waiting for the first successful response. Server error 1 will be ignored!'],
[// ---------------- =>fn()//Comment (Promise.Any.Test.php8:22)
'From' => 'Выведет данные с сервера 3',
'To'   => 'Will output data from server 3'],
[// ---------------- //Comment (Promise.Any.Test.php8:31)
'From' => 'Запускаем наш Event Loop для обработки обоих кейсов',
'To'   => 'We launch our event loop to handle both cases.'],
[// ---------------- \Promise::race()//Doc (Promise.php:199)
<<<'TranslateFrom'
Возвращает промис, который завершится так же и с тем же результатом,
как и первый завершившийся промис из массива (успех или ошибка).
TranslateFrom,
<<<'TranslateTo'
Returns a promise that will resolve in the same way and with the same result,
just like the first promise from the array to settle (whether successfully or with an error).
TranslateTo],
[// ---------------- \Promise::race()//Comment (Promise.php:209)
<<<'TranslateFrom'
По спецификации пустой race() навечно остается в состоянии PENDING.
Но для удобства можно либо выбросить исключение, либо оставить так.
TranslateFrom,
<<<'TranslateTo'
According to the specification, an empty race() remains in the PENDING state forever.
However, for convenience, you can either throw an exception or leave it as is.
TranslateTo],
[// ---------------- \Promise::race()//Comment (Promise.php:219)
<<<'TranslateFrom'
Кто первый вызвал метод у deferred, тот и зафиксировал состояние.
Повторные вызовы resolve/reject внутри Deferred просто проигнорируются.
TranslateFrom,
<<<'TranslateTo'
Whoever called a method on the deferred object first is the one who locked in the state.
Subsequent calls to resolve/reject within a Deferred are simply ignored.
TranslateTo],
[// ---------------- \Promise::any()//Doc (Promise.php:230)
<<<'TranslateFrom'
Возвращает промис, который выполнится успешно, как только выполнится успешно
хотя бы один из промисов в массиве.
Если все промисы отклонены, возвращает ошибку AggregateException.
TranslateFrom,
<<<'TranslateTo'
Returns a promise that resolves as soon as
at least one of the promises in the array resolves. 
If all promises are rejected, returns an AggregateException.
TranslateTo],
[// ---------------- \Promise::any()//String (Promise.php:241)
'From' => '"Promise::any() передан пустой массив."',
'To'   => '"An empty array was passed to Promise::any()."'],
[// ---------------- \Promise::any()//Comment (Promise.php:252)
'From' => 'Обычное значение считается мгновенным успехом',
'To'   => 'The standard value is considered an instant success.'],
[// ---------------- \Promise::any()=>fn()//Comment (Promise.php:260)
'From' => 'Первый же успех резолвит общий промис',
'To'   => 'The first success resolves the general promise'],
[// ---------------- \Promise::any()=>fn()//Comment (Promise.php:268)
'From' => 'Если упали ВСЕ промисы, отклоняем общий промис',
'To'   => 'If all promises have failed, reject the overall promise.'],
[// ---------------- \Promise::any()=>fn()//String (Promise.php:270)
'From' => '"Все промисы были отклонены"',
'To'   => '"All promises were rejected."'],
[// ---------------- \AggregateException//Doc (Exception/Aggregate.php:2)
<<<'TranslateFrom'
Кастомное исключение для метода Promise::any(),
хранящее ошибки всех упавших промисов.
TranslateFrom,
<<<'TranslateTo'
A custom exception for the Promise::any() method,
storing the errors of all rejected promises.
TranslateTo],
[// ---------------- =>fn()//Comment (Promise.Race.Test.php8:7)
'From' => 'Имитируем запрос, который длится 3 секунды',
'To'   => 'We simulate a request that takes 3 seconds.'],
[// ---------------- =>fn()//Comment (Promise.Race.Test.php8:10)
'From' => 'Создаем промис-таймаут, который упадет через 1.5 секунды',
'To'   => 'We create a promise timeout that rejects after 1.5 seconds.'],
[// ---------------- =>fn()//Comment (Promise.Race.Test.php8:17)
<<<'TranslateFrom'
Запускаем гонку!
TranslateFrom,
<<<'TranslateTo'
We're kicking off the race!
TranslateTo],
[// ---------------- =>fn()//Comment (Promise.Race.Test.php8:23)
'From' => 'Сработает таймаут, так как 1.5с < 3.0с',
'To'   => 'The timeout will trigger, since 1.5s < 3.0s.'],
[// ---------------- \Promise::timeout()//Doc (Promise.php:278)
<<<'TranslateFrom'
Ограничивает время выполнения текущего промиса.
Если промис не успевает выполниться за $seconds секунд, возвращается отклонённый промис.
TranslateFrom,
<<<'TranslateTo'
Limits the execution time of the current promise.
If the promise does not resolve within $seconds seconds, a rejected promise is returned.
TranslateTo],
[// ---------------- \Promise::timeout()//Comment (Promise.php:284)
<<<'TranslateFrom'
Создаем промис-таймер, который гарантированно упадет через указанное время.
Для создания таймера используем наш Loop::delay.
TranslateFrom,
<<<'TranslateTo'
Create a promise-based timer that is guaranteed to reject after the specified time.
To create a timer, we use our Loop::delay.
TranslateTo],
[// ---------------- \Promise::timeout()//Comment (Promise.php:293)
<<<'TranslateFrom'
Запускаем гонку между текущим промисом ($this) и промисом-таймером.
Возвращаем результат этой гонки.
TranslateFrom,
<<<'TranslateTo'
Start a race between the current promise ($this) and the timer promise.
Return the result of this race.
TranslateTo],
[// ---------------- \longApiCall()//Comment (Promise.TimeOut.Test.php8:4)
'From' => 'Имитируем долгий сетевой запрос к API',
'To'   => 'Simulating a long-running network request to the API.'],
[// ---------------- \longApiCall()//Comment (Promise.TimeOut.Test.php8:9)
'From' => 'Ответ придет только через 3 секунды',
'To'   => 'The answer will arrive only after 3 seconds.'],
[// ---------------- //Comment (Promise.TimeOut.Test.php8:17)
'From' => 'Процесс 1: Запрос, который НЕ уложится в таймаут',
'To'   => 'Process 1: A request that will NOT time out.'],
[// ---------------- =>fn()//Comment (Promise.TimeOut.Test.php8:23)
'From' => 'Просто вызываем метод цепочкой прямо у промиса!',
'To'   => 'We simply call the method in a chain directly on the promise!'],
[// ---------------- =>fn()//Comment (Promise.TimeOut.Test.php8:29)
'From' => 'Сработает этот блок, так как 1.0с < 3.0с',
'To'   => 'This block will execute, since 1.0s < 3.0s.'],
[// ---------------- //Comment (Promise.TimeOut.Test.php8:34)
'From' => 'Процесс 2: Запрос, который УЛОЖИТСЯ в таймаут (дали ему 4 секунды)',
'To'   => 'Process 2: A request that fits within the timeout (it was given 4 seconds)'],
[// ---------------- =>fn()//Comment (Promise.TimeOut.Test.php8:36)
<<<'TranslateFrom'
Немного подождем, чтобы логи не перемешивались слишком хаотично
TranslateFrom,
<<<'TranslateTo'
Let's wait a bit so the logs don't get mixed up too chaotically.
TranslateTo],
[// ---------------- =>fn()//Comment (Promise.TimeOut.Test.php8:42)
'From' => 'Можно кастомизировать текст ошибки при желании',
'To'   => 'You can customize the error text if desired.'],
[// ---------------- //Comment (Promise.TimeOut.Test.php8:53)
'From' => 'Запуск цикла событий',
'To'   => 'Starting the event loop'],
[// ---------------- =>fn()//Comment (Promise.AllSettled.Test.php8:8), =>fn()=>fn()//Comment (Promise.AllSettled.Test.php8:10)
'From' => 'Успешно вернет баланс',
'To'   => 'It will successfully restore balance.'],
[// ---------------- =>fn()//Comment (Promise.AllSettled.Test.php8:9)
'From' => 'Упадет',
'To'   => 'It will fall'],
[// ---------------- =>fn()//Comment (Promise.AllSettled.Test.php8:13)
'From' => 'Метод allSettled() никогда не выбросит исключение в await!',
'To'   => 'The allSettled() method will never throw an exception when awaited!'],
[// ---------------- =>fn()//Comment (Promise.AllSettled.Test.php8:22)
<<<'TranslateFrom'
В поле 'reason' лежит Throwable или строка ошибки
TranslateFrom,
<<<'TranslateTo'
The 'reason' field contains a Throwable or an error string.
TranslateTo],
[// ---------------- //Comment (Promise.AllSettled.Test.php8:27)
'From' => 'Запускаем Event Loop',
'To'   => 'Starting the Event Loop'],
[// ---------------- \Promise::allSettled()//Doc (Promise.php:298)
<<<'TranslateFrom'
Ожидает завершения всех промисов (успешного или с ошибкой).
Возвращает массив структур: 
['status' => 'fulfilled', 'value' => $value] или ['status' => 'rejected', 'reason' => $reason]
TranslateFrom,
<<<'TranslateTo'
Waits for all promises to complete (successfully or with an error).
Returns an array of structures:
['status' => 'fulfilled', 'value' => $value] или ['status' => 'rejected', 'reason' => $reason]
TranslateTo],
[// ---------------- \Promise::allSettled()//Comment (Promise.php:320)
<<<'TranslateFrom'
Если передали обычное значение - это моментальный успех
TranslateFrom,
<<<'TranslateTo'
If you convey the ordinary meaning, it's an instant success.
TranslateTo],
[// ---------------- \Promise::allSettled()//Comment (Promise.php:324)
'From' => 'Нам важен сам факт завершения, поэтому логика сбора идентична для обоих колбэков',
'To'   => 'The fact of completion itself is what matters to us, so the collection logic is identical for both callbacks.'],
[// ---------------- \AbortController//Doc (AbortController.php:2)
'From' => 'Управляющий объект для отмены задач',
'To'   => 'Task cancellation manager'],
[// UNUSED ----------------
'From' => 'Токен отмены, передаваемый внутрь асинхронных функций',
'To'   => 'Cancellation token passed into asynchronous functions'],
[// UNUSED ----------------
'From' => 'Позволяет асинхронной функции подписаться на событие отмены',
'To'   => 'Allows an asynchronous function to subscribe to a cancellation event.'],
[// ---------------- \AbortSignal::triggerAbort()//Doc (AbortSignal.php:41)
'From' => 'Внутренний метод, вызываемый только из AbortController',
'To'   => 'Internal method called only from AbortController'],
[// ---------------- \AbortSignal::triggerAbort()//String (AbortSignal.php:48)
'From' => '"Операция была отменена."',
'To'   => '"The operation was cancelled."'],
[// ---------------- //Comment (AbortSignal.Test.php8:4)
'From' => 'Подготовим файл',
'To'   => 'We will prepare the file.'],
[// ---------------- =>fn()//Comment (AbortSignal.Test.php8:9)
'From' => '1. Создаем контроллер отмены',
'To'   => '1. Create a cancellation controller.'],
[// ---------------- =>fn()//Comment (AbortSignal.Test.php8:13)
'From' => '2. Имитируем внешнее событие: через 20 миллисекунд отменяем операцию',
'To'   => '2. Simulate an external event: cancel the operation after 20 milliseconds.'],
[// ---------------- =>fn()//Comment (AbortSignal.Test.php8:24)
'From' => 'Передаем сигнал и в саму операцию, и в await',
'To'   => 'We pass the signal both to the operation itself and to the await.'],
[// ---------------- =>fn()//Comment (AbortSignal.Test.php8:31)
'From' => 'Ловим именно наше кастомное исключение отмены',
'To'   => 'We catch specifically our custom cancellation exception.'],
[// ---------------- \AbortException//Doc (Exception/Abort.php:2)
'From' => 'Исключение, выбрасываемое при отмене операции',
'To'   => 'Exception thrown when an operation is cancelled'],
[// UNUSED ----------------
'From' => 'Флаг для внутренней остановки рекурсии',
'To'   => 'Flag for internal recursion termination'],
[// ---------------- \FileReaderChunk::readChunk()//Comment (FileReaderChunk.php:50)
'From' => 'Если была отмена, выходим',
'To'   => 'If there was a cancellation, we exit.'],
[// UNUSED ----------------
'From' => '1. Проверяем, не отменено ли всё еще до начала ожидания',
'To'   => '1. Check whether cancellation has already occurred before the wait begins.'],
[// UNUSED ----------------
'From' => '2. Если передан сигнал, подписываемся на него',
'To'   => '2. If a signal is transmitted, we subscribe to it.'],
[// UNUSED ----------------
'From' => 'Если файбер все еще на паузе, бросаем в него исключение отмены',
'To'   => 'If the fiber is still paused, throw a cancellation exception into it.'],
[// ---------------- \Promise::$onCancel//Comment (Promise.php:11)
'From' => 'Колбэк, который вызывается при отмене для очистки ресурсов (стримов, таймеров)',
'To'   => 'A callback invoked upon cancellation to clean up resources (streams, timers).'],
[// ---------------- \Promise::setOnCancel()//Comment (Promise.php:14)
'From' => 'Метод для установки колбэка очистки (используется внутри AsyncFile/httpGet)',
'To'   => 'Method for setting a cleanup callback (used within AsyncFile/httpGet)'],
[// ---------------- \Promise::abort()//Doc (Promise.php:27)
'From' => 'Принудительная отмена конкретного промиса',
'To'   => 'Forced cancellation of a specific promise'],
[// ---------------- \Promise::abort()//String (Promise.php:34)
'From' => '"Промис был отменен."',
'To'   => '"The promise was cancelled."'],
[// ---------------- \Promise::abort()//Comment (Promise.php:37)
'From' => '1. Вызываем очистку системных ресурсов, если она была задана',
'To'   => '1. Invoke system resource cleanup if it has been specified.'],
[// ---------------- \Promise::abort()//Comment (Promise.php:41)
'From' => '2. Отклоняем промис с ошибкой AbortException',
'To'   => '2. Reject the promise with an AbortException.'],
[// ---------------- \AbortSignal//Doc (AbortSignal.php:2)
'From' => 'Токен отмены, передаваемый внутрь асинхронных функций или шлёт событие отмены промисам.',
'To'   => 'A cancellation token passed into asynchronous functions or used to send a cancellation event to promises.'],
[// ---------------- \AbortSignal::$associatedPromises//Comment (AbortSignal.php:10)
'From' => 'Храним связанные промисы',
'To'   => 'We store the associated promises.'],
[// ---------------- \AbortSignal::onAbort()//Doc (AbortSignal.php:15)
'From' => 'Позволяет старой асинхронной функции подписаться на событие отмены',
'To'   => 'Allows an old asynchronous function to subscribe to a cancellation event.'],
[// ---------------- \AbortSignal::follow()//Doc (AbortSignal.php:28)
'From' => 'Привязывает промис к этому сигналу.',
'To'   => 'Binds the promise to this signal.'],
[// ---------------- \AbortSignal::triggerAbort()//Comment (AbortSignal.php:50)
'From' => '1. Оповещаем обычных слушателей',
'To'   => '1. Notifying general listeners'],
[// ---------------- \AbortSignal::triggerAbort()//Comment (AbortSignal.php:55)
'From' => '2. Раздаем сигнал отмены всем привязанным промисам!',
'To'   => '2. Broadcast the cancellation signal to all linked promises!'],
[// ---------------- \FileReaderChunk::readAll()//Comment (FileReaderChunk.php:35)
'From' => 'Регистрируем логику отмены прямо в промисе!',
'To'   => 'We register the cancellation logic right inside the promise!'],
[// ---------------- //Comment (Promise.Abort.Test.php8:7)
'From' => 'Подготовим тестовые файлы',
'To'   => 'We will prepare the test files.'],
[// UNUSED ----------------
'From' => 'Создаем промисы чтения файлов',
'To'   => 'Creating file-reading promises'],
[// UNUSED ----------------
'From' => 'Навешиваем их на один AbortController / AbortSignal',
'To'   => 'We attach them to a single AbortController / AbortSignal.'],
[// ---------------- =>fn()//Comment (Promise.Abort.Test.php8:22)
'From' => 'Имитируем отмену всей группы задач через 15 миллисекунд',
'To'   => 'We simulate the cancellation of the entire task group after 15 milliseconds.'],
[// ---------------- =>fn()=>fn()//Comment (Promise.Abort.Test.php8:28)
'From' => 'Функция для безопасного await',
'To'   => 'Function for safe await'],
[// ---------------- =>fn()//Comment (Promise.Abort.Test.php8:41)
'From' => 'Запускаем ожидание',
'To'   => 'Starting the wait process.'],
[// ---------------- =>fn()//Comment (Promise.Abort.Test.php8:17)
'From' => 'Создаем промисы чтения файлов и навешиваем их на один AbortController / AbortSignal',
'To'   => 'We create file-reading promises and attach them to a single AbortController / AbortSignal.'],
[// ---------------- \Promise::abortBy()//Doc (Promise.php:21)
<<<'TranslateFrom'
Привязывает этот промис к указанному сигналу отмены.
Возвращает текущий промис для чейнинга (Fluent API).
TranslateFrom,
<<<'TranslateTo'
Binds this promise to the specified cancellation signal.
Returns the current promise for chaining (Fluent API).
TranslateTo],
];