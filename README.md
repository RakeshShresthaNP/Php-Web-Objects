Simple/Easy to learn and high-performance, lightweight PHP MVC framework that bridges the gap between traditional web development and cutting-edge data science. By combining a minimalist core with powerful Gemini-powered AI, Machine Learning, and Financial Analytics libraries, it allows developers to build complex, secure, and data-driven applications without the bloat. Whether you are managing Dynamic Website (Traditional or Modern Ajax Powered) or Restful API services or data driven mechine learning and AI powered application, PHP Web Objects provides the tools you need - loaded only when you need them.

<img src="https://github.com/RakeshShresthaNP/Php-Web-Objects/blob/main/screen/dashboard2.png?raw=true" alt="Dashboard" />

## Table of Contents
* [Core Architecture and Routing](#1-core-architecture-and-routing)
* [Database and Storage](#2-database-and-storage)
* [Artificial Intelligence](#3-artificial-intelligence)
    * [Document Workflow Automation](#document-workflow-automation)
    * [Speech & Media](#speech--media)
* [Machine Learning (ML) Suite](#4-machine-learning-ml-suite)
* [Quantitative Finance and Analytics](#5-quantitative-finance-and-analytics)
* [Security and Extended Libraries](#6-security-and-extended-libraries)
* [Permission Management Logic](#7-permission-management-logic)
* [Performance Comparison (Node.js vs. Python vs. PWO Framework)](#8-performance-comparison)
* [Benchmarks & Resource Efficiency](#9-benchmarks--resource-efficiency)

### Password Values
* superadmin@gmail.com = Mytest123
* user@gmail.com = Mytest123

---

### 1. Core Architecture and Routing
* **Modern MVC Framework:** Lightweight architecture inspired by CodeIgniter and CakePHP.
* **Intelligent Routing:** Seamless URI-to-controller mapping for clean, SEO-friendly URLs.
* **Native Dependency Injection:** High-efficiency DI mechanism ensuring libraries load only when required.
* **Extensible View Engine:** Supports PHP-native views through a high-performance, minimalist template engine.

---

### 2. Database and Storage

A PHP Web Objects framework provides lightweight, fluent model class optimized for streamlined CRUD operations, engineered to provide Laravel-inspired developer experience with extreme performance efficiency. At its core, this ORM features a built-in Graph Engine that leverages native PDO support and modern MySQL JSON functions (JSON_OBJECT, JSON_ARRAYAGG) to fetch complex, nested relationships in a single, high-performance database hit. By transforming relational data into hierarchical JSON structures at the storage layer, it completely eliminates the N+1 query problem, drastically reducing latency and server overhead compared to traditional "Lazy Loading" or "Eager Loading" techniques that require multiple round-trips to the database.

* **Universal PDO Support:** Native multi-database compatibility for secure, prepared statements.
* **One-Query Nesting:** Fetch parents and children (1:N) as a single nested JSON object using the built-in Graph Engine.

#### ORM Usage Example
```php
$user = new partner();
// Standard CRUD
$users = $user->select('id, username')->find();

// High-Performance GraphQL-Style Nesting
$schema = [
    'id' => 'id',
    'settings' => [
        'table' => 'mst_partner_settings',
        'foreign_key' => 'partner_id',
        'fields' => ['mailhost' => 'mailhost']
    ]
];
$results = $user->paginateGraph($schema, 1, 10);
```

* **Modular Caching:** Handlers for Redis, Memcached, and local SessionStorage.
* **Cloud Integration:** Native bridge for Amazon S3 storage and file management.

---

### 3. Artificial Intelligence

* **Multimodal Input:** Native support for Text, Image, and Video-based prompts.
* **Conversational Intelligence:** Multi-turn Chat, Streaming responses, and "Thinking Mode."

#### Document Workflow Automation
**1. Advanced Document Processing**
* **Multimodal Extraction:** Can extract structured data (JSON/CSV) from unstructured sources like handwritten notes, complex financial tables, and long-form contracts.
* **Document Intelligence:** Natively understand various document layouts, charts, spatial relationships, and formatting rather than just reading plain text.
* **High-Volume Processing:** Can process up to 1 million tokens (thousands of pages) in a single request, making it efficient to digitize massive document backlogs.

**2. Document Classification**
* **Zero-Shot Classification:** Can classify incoming documents into specific categories (e.g., Invoice, Legal Agreement, Resume) based on content and visual structure without manual pre-labeling.
* **Nuanced Categorization:** Can distinguish between similar document types, such as identifying if a legal document is an NDA versus a Service Agreement by looking for specific clauses.
* **Sentiment & Intent:** Can classify documents by tone or urgency, which is useful for triaging support tickets or high-priority emails.

**3. Information Workflow Management**
* **Chaining Tasks:** Can act as an orchestrator to classify a document, decide which extraction template to use, and format the result for a specific database in a single workflow.
* **Cross-Document Synthesis:** Can reason across multiple files simultaneously to find discrepancies, summarize themes, or perform compliance checks across a whole dataset.
* **Tool Use (Function Calling):** Can be connected to internal APIs to move data automatically, such as extracting an invoice total and then calling a payment function.

#### Speech & Media - Integrated Speech generation (TTS) and Image Generation capabilities.

**1. Advanced Speech Intelligence**
* **Native Speech-to-Text:** Can transcribe audio files up to 8.4 hours long in a single request, providing high-accuracy text outputs with precise timestamps for every word or sentence.
* **Speaker Diarization & Emotion Detection:** Can distinguish between different speakers in a recording and detect nuances like tone, sentiment, and emotional state (e.g., identifying a frustrated customer vs. a satisfied one).
* **Multilingual Translation:** Support seamless translation across 24+ languages, allowing for the transcription of a foreign language recording directly into English (or vice versa) while maintaining context.

**2. Comprehensive Media & Video Analysis**
* **Native Video Understanding:** Can "watch" videos up to 45 minutes long, identifying objects, scenes, and actions without needing a manual transcript or frame-by-frame breakdown.
* **Temporal Reasoning:** You can ask specific questions about when an event occurred (e.g., "At what time did the presenter mention the budget?"), and the system will provide the exact timestamp from the video.
* **Visual-Spatial Intelligence:** Can describe the layout of a scene, read text appearing on screen, and summarize complex visual data like charts or infographics within a video stream.

**3. Real-Time Media Workflows**
* **Live Interaction:** Support bidirectional, low-latency voice conversations where you can interrupt, change the topic, or ask to "look" through your camera to explain.
* **Content Generation & Synthesis:** Can generate summaries, chapters, and metadata for podcasts or webinars, and even synthesize "NotebookLM-style" audio overviews from a set of text documents.
* **Multimodal Orchestration:** Can reason across different media types simultaneously—for example, comparing an audio recording of a meeting against a PDF contract to find discrepancies.

---

### 4. Machine Learning (ML) Suite
* **Predictive Analytics:** Rule Learning, Classification, Regression, and Neural Networks.
* **Data Processing:** Preprocessing, Feature Extraction, and Feature Selection routines.
* **Clustering & Validation:** Advanced Clustering algorithms like K-Means and DBSCAN with built-in Cross-Validation.
* **Model Management:** Dedicated tools for managing and deploying ML models.

---

### 5. Quantitative Finance and Analytics
* **Market Analysis:** Time Series Analysis, Options Pricing, and Regression Analysis.
* **Risk Metrics:** Calculation of Value at Risk (VaR), Conditional VaR (CVaR), and Max Drawdown.
* **Portfolio Analytics:** Active Share, Tracking Error, Information Ratio, and Sharpe Ratio.

---

### 6. Security and Extended Libraries
* **Hardened Security:** Built-in protection against XSS and SQL Injection.
* **Auth & API:** Integrated Authentication scaffolding and JWT API support.
* **Modern UI:** Dashboard integration with pre-built visual elements. For AI compatibility and further info https://github.com/RakeshShresthaNP/VWEB-Dashboard
* **Extended Libraries:** Event/Queue Management Mechanism (myapp/app/Readme), AuditLog Data Structure, Mailer(Native PHP or SMTP), Support for Firebase Messaging, SMPP (SMSC), QR Codes, AES Encryption, Time based OTP, Excel (XLSX) Import, Pagination, and SoapClient for NTLMStream.

---

### 7. Permission Management Logic
The framework utilizes a robust, database-driven **Access Control** system to manage granular permissions:

* **Module-Level Access:** Control which user roles can see specific sections of the application (e.g., Admin, Finance, Editor).
* **Function-Level Granularity:** Define permissions down to specific actions (e.g., `delete_user`, `export_csv`).
* **Dynamic Evaluation:** Permissions are checked in real-time against the database, allowing for instant access revocation without requiring code changes or redeployment.

---

### 8. Performance Comparison
This framework is engineered for environments where performance, disk space, and resource efficiency are critical. Unlike modern runtimes that suffer from "dependency bloat," this architecture prioritizes a **minimal footprint**.

| Feature | This Framework | Node.js (Express/Nest) | Python (Django/FastAPI) |
| :--- | :--- | :--- | :--- |
| **Memory Footprint** | **Ultra-Low (OPCache PHP)** | High (V8 Engine overhead) | Moderate to High (Interpreter) |
| **App Library Size** | **Slim (< 2.5MB Core)** | Massive (`node_modules`) | Large (Virtual Envs / Libs) |
| **Dependency Model** | Native / Integrated | NPM (Third-party heavy) | Pip (Heavy external pkgs) |
| **Cold Start Time** | Near-Instant | Moderate | Slow (especially Django) |

---

### 9. Benchmarks & Resource Efficiency
Our core design principle is **"Zero-Waste Architecture."** By using a native Dependency Injection mechanism and a slim MVC core, we achieve significantly higher throughput on lower-tier hardware.

#### 📦 Library & Disk Size Advantage
Traditional frameworks require downloading thousands of external files to achieve basic functionality. This framework includes its AI, ML, and Finance suites as highly optimized, native components.

* **This Framework Core:** ~2MB - 3.5MB (Full featured)
* **Typical Node.js Project:** 600MB - 1.2GB (due to `node_modules`)
* **Typical Python Project:** 250MB - 4GB (due to `site-packages` based on standard django app or cutting edge AI/ML applications)

#### 🚀 Memory Usage Per Request

* **This Framework:** ~2MB - 5MB (Idle - Full AI/ML Library Calls)
* **Node.js (Express):** ~15MB - 30MB (Idle)
* **Python (Django):** ~40MB - 70MB (Idle)

#### Key Efficiency Metrics:
1.  **Lazy Loading:** Libraries are never loaded into memory unless the specific controller requires them.
2.  **Stateless Execution:** Unlike Node.js, which maintains a persistent heap that grows over time, our framework clears memory after every request.
3.  **No Middleware Bloat:** While other frameworks require 20+ "packages" for basic security and routing, this framework handles these natively within a single coreroutines file.

> **Why it matters:** Lower library size means faster deployment, smaller Docker images, and lower storage costs on cloud providers. It is very simple to say using less libraries we decrease processing also.
















