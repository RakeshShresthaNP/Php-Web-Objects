The PHP Web Objects (PWO) Worker is a high-performance, background CLI process designed to handle heavy lifting—such as AI multimodal processing, automated document workflows, and SMTP mailing—without blocking the web request cycle.

---

### 1. Linux Setup (Production with Supervisor)

In a production environment, you must ensure the worker is a "Daemon" (always running). We recommend Supervisor.

Install Supervisor
```Bash
sudo apt-get install supervisor
```

Configure the Worker

Create a configuration file: /etc/supervisor/conf.d/pwo-worker.conf
```Ini, TOML
[program:pwo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/your-project/bin/worker.php
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/your-project/logs/worker.log
```

Start the Service
```Bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start pwo-worker:*
```

---

### 2. Windows Setup (Production with NSSM)

Since Windows does not natively manage PHP daemons well, use NSSM (Non-Sucking Service Manager).

1. Download: NSSM.cc

2. Install: Open Terminal as Administrator and run:
	```Dos
	sudo supervisorctl reread
	sudo supervisorctl update
	sudo supervisorctl start pwo-worker:*
	```

3. Configuration:

	Path: C:\php\php.exe

	Startup Directory: C:\path\to\your\project\bin

	Arguments: worker.php

4. Registry: Click "Install Service" and start it via services.msc.

---

### 3. Worker Lifecycle & Performance

The PWO Worker is built with "Zero-Waste" logic:

Memory Management: The worker monitors its own memory usage. If it exceeds 64MB (configurable), it exits gracefully. Supervisor/NSSM will then restart it immediately with a clean 0MB heap.

Exponential Backoff: If a task (like an AI API call or SMTP send) fails, the worker updates the available_at time. Retries occur at 5-minute, 15-minute, and 1-hour intervals.

Catch-All Resilience: Uses catch(\Throwable) to ensure that even a syntax error in a job handler won't crash the entire service; it simply logs the error to the database and moves to the next job.
	

---
	
### 4. Adding a New Job Handler

To extend the worker, follow the OOP pattern:

Create a class QueueNewClass in app/queues/QueueNewClass.php implementing JobHandlerInterface.

Register the task name in the QueueWorker constructor:
```Dos
$this->handlers['your_task_name'] = new QueueNewClass();
```