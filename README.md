# php-daemon
Class that helps the developing of daemons in php. 

General Description

Class that helps the development of daemons in php.
The daemon can have childrens, and can manage its terminations. Can run in one or in multiple instances, each ones with its childrens. A event system give access to external code helps in develop the functionality of the daemon without write code inside the class.

Requirements: php-process pear-proctitle.

Based on original works found at: http://php.net/manual/ru/function.pcntl-fork.php, http://www.electrictoolbox.com/check-php-script-already-running/ and others sources of inspiration.

Details (Information in spanish for now)
Eventos que dispara el daemon

onMultiInstance: Se dispara cuando se quiere correr mas de una instancia del demonio y no esta permitido.
onRunTerminated: Cuando ya no quedan hijos para lanzar y run terminara.
onLauncher: Inmediatamente antes de forkear, para que el padre sepa que clase de tareas ejecutara el hijo
onLaunchJobError: Cuando quiere forkear pero hay un error en el proceso
onLaunchJob: Cuando forkea. Aca hay que desarrollar la logica de la aplicacion. Tener en cuenta resultado de onLauncher
daemonHupSignalReceived: cuando el daemon recibe un HUP y va a mandar los hups a los hijos
daemonHupSignalProcesed: cuando el daemon recibe un HUP y ya proceso el envio de los hups a los hijos
daemonTermSignalReceived: cuando el daemon recibe un TERM y va a mandar hups a los hjos
daemonTerminating: cuando el daemon recibe un TERM y ya mando los hups a los hijos. Va a morirse.
onChildTerminated: lo dispara EL PADRE cuando muere un hijo (o va a matar uno).

El evento pasa por referencia a la clase daemon, para poder controlar sus propiedades desde adentro de la funcion
onChildTerminated pasa ademas el pid del evento que murio para que el evento pueda buscar en la lista de objetos el correspondiente al pid y ejecutar las rutinas de cierre

Propiedades:
maxProcesses: cantidad maxima de hijos corriendo
childsProcName: el nombre de los procesos hijos en la tabla de procesos
multiInstances: TRUE/FALSE. True permite multiples Instancias
procName: nombre del proceso. Es utilizado para pidfile y para renombrar a los procesos hijos. Default daemon
pidFileDir: Directorio del pidfile, por default /var/run
childObject: Objeto que se puede cargar en los hijos para ser compartido entre eventos

Funciones:
bind(evento,funcion_callable): configura las funciones que deben dispararse al ocurrir los eventos
run() inicia el demonio

construct($multi=FALSE);

Requerimientos: php process pear proctitle.

Posix Signals
SIGHUP: mata procesos hijos
SIGTERM: mata procesos hijos y padre.



