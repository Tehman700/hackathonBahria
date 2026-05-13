# AtomCamp Smart Adaptive LMS — Project Progress

## Project Strategy

**Main LMS: Moodle 5.2** (the full-featured, production-grade LMS platform)

**Secondary: CogniPath** (AI engine — selected features from it will be integrated INTO Moodle)

### Why Moodle as the core?
Moodle already has everything a real LMS needs out of the box:
- Course & content management
- Quiz & assessment engine
- Gradebook & progress tracking
- User roles (student, teacher, admin)
- Discussion forums
- Certificates of completion
- Enrollment management
- Calendar & scheduling
- Reporting & analytics

### What we're adding from CogniPath
CogniPath brings the AI-adaptive layer that Moodle lacks. We are integrating these CogniPath features into Moodle as custom plugins/blocks:

| CogniPath Feature | Integration into Moodle |
|---|---|
| AI Learning Path Generator | Moodle plugin: auto-generates a personalized course sequence for each student |
| Socratic AI Tutor Chat | Moodle block: per-course AI chatbot that uses course content as context |
| AI Lesson Content Generator | Moodle plugin: generates lesson summaries and explanations from course material |
| AI Quiz Generator | Moodle plugin: auto-generates MCQ questions from lesson content |
| Adaptive Difficulty Engine | Moodle plugin: adjusts next content recommendation based on quiz scores |
| AI Progress Insights | Moodle dashboard block: instructor view of struggling students with AI suggestions |

### Integration Architecture

```
[ Moodle LMS (PHP + Apache + MySQL) ]   <-- Main application
        |
        | REST API calls
        v
[ Flask AI Backend (Python + Gemini) ]  <-- CogniPath's AI engine
        |
        | Gemini 2.5 Flash
        v
[ Google Gemini API ]
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Main LMS | Moodle 5.2 (PHP 8.2 + Apache) |
| LMS Database | MySQL 8.0 |
| AI Backend | Python Flask + Gemini 2.5 Flash (from CogniPath) |
| AI Model | Google Gemini 2.5 Flash Lite |
| Containerization | Docker + Docker Compose |
| Custom Plugins | PHP (Moodle plugin API) |

---

## How to Run

### Prerequisites
- Docker Desktop running
- A Gemini API Key from https://aistudio.google.com/apikey

---

### Run Moodle (Main LMS)

```powershell
cd "d:\LMS Hackathon\moodle_LMS"
docker-compose build
docker-compose up -d
```

Open **http://localhost:8090** → follow the install wizard.

**DB credentials for install wizard:**
| Field | Value |
|---|---|
| Database host | `moodledb` |
| Database name | `moodle` |
| Database user | `moodle` |
| Database password | `moodle123` |
| Moodle data directory | `/var/moodledata` |

---

### Run CogniPath AI Backend (Flask API)

```powershell
cd "d:\LMS Hackathon\CogniPath\CogniPathLMS\server"
venv\Scripts\activate          # first time: python -m venv venv && pip install -r requirements.txt
python run.py
# Runs at http://localhost:8080
```

Add your key to `server/.env`:
```
GOOGLE_API_KEY=your_key_here
PORT=8080
```

---

### Run CogniPath Frontend (standalone demo only)

```powershell
cd "d:\LMS Hackathon\CogniPath\CogniPathLMS\client"
npm install
npm run dev
# Runs at http://localhost:5173
```

> Note: The CogniPath frontend is a standalone demo of AI path generation.
> The real integration target is Moodle — not this frontend.

---

## Modules To Build (Moodle Integration)

### MODULE 1 — AI Learning Path Generator Plugin
**Priority: HIGH**
- Moodle plugin that calls the Flask `/api/generate-path` endpoint
- Student enters their goal and skill level inside Moodle
- AI returns a personalized sequence of existing Moodle courses/modules to follow
- Saves the recommended path to the student's Moodle profile
- **Type:** Moodle local plugin (`local_ai_pathgen`)
- **Status:** Not started

---

### MODULE 2 — Socratic AI Tutor Chat Block
**Priority: HIGH**
- Moodle block added to any course page
- Calls Flask `/api/chat` with the course's content as context
- Uses Socratic method — guides students with questions instead of direct answers
- Chat history persisted per student per course
- **Type:** Moodle block plugin (`block_ai_tutor`)
- **Status:** Not started

---

### MODULE 3 — AI Quiz Question Generator
**Priority: HIGH**
- Moodle plugin for teachers: select a lesson/topic → AI generates MCQ questions
- Questions auto-imported into Moodle's native Quiz activity
- Calls Flask `/api/generate-quiz` (new endpoint to build in Flask)
- Teachers review and approve before publishing
- **Type:** Moodle local plugin (`local_ai_quizgen`)
- **Status:** Not started

---

### MODULE 4 — Adaptive Difficulty Engine
**Priority: HIGH**
- After a student completes a Moodle quiz, this plugin reads their score
- Score < 60%: recommends review resources, flags to instructor
- Score 60–79%: proceeds normally  
- Score ≥ 80%: suggests advanced content or next module
- AI-generated personalized tip shown to student after each quiz
- **Type:** Moodle local plugin (`local_ai_adaptive`)
- **Status:** Not started

---

### MODULE 5 — AI Progress Insights Dashboard (Instructor View)
**Priority: MEDIUM**
- New Moodle dashboard block for teachers/admins
- Shows: class-wide progress, average scores, struggling students flagged
- AI-generated insight: "3 students are struggling with Module 2 — consider adding a review session"
- Calls Gemini with student performance data to generate natural-language insights
- **Type:** Moodle block plugin (`block_ai_insights`)
- **Status:** Not started

---

### MODULE 6 — AtomCamp Course Templates
**Priority: MEDIUM**
- Pre-built Moodle course templates based on AtomCamp's real programs:
  - AI & Machine Learning Bootcamp
  - Web Development Bootcamp
  - Data Science Bootcamp
  - Cloud & DevOps Track
- Importable as Moodle backup files (.mbz)
- Each course has AI tutor block pre-installed
- **Status:** Not started

---

### MODULE 7 — AI Lesson Summarizer
**Priority: LOW**
- Moodle block: student clicks "Summarize this lesson" button
- Sends lesson page content to Flask → Gemini → returns bullet-point summary
- Helps students review before quizzes
- **Type:** Moodle block plugin (`block_ai_summarizer`)
- **Status:** Not started

---

## What Moodle Already Provides (No Need to Build)

| Feature | Moodle Built-in |
|---|---|
| Course management | ✅ Native |
| Student/Teacher/Admin roles | ✅ Native |
| Quiz & grading engine | ✅ Native |
| Gradebook & progress tracking | ✅ Native |
| Discussion forums | ✅ Native |
| Certificates of completion | ✅ Native plugin |
| File uploads & media | ✅ Native |
| Calendar & deadlines | ✅ Native |
| Mobile-responsive UI | ✅ Native (Boost theme) |
| Email notifications | ✅ Native |
| Enrollment management | ✅ Native |

---

## API Endpoints (Flask AI Backend)

| Method | Endpoint | Status | Used By |
|---|---|---|---|
| POST | `/api/generate-path` | ✅ Working | MODULE 1 |
| POST | `/api/chat` | ✅ Working | MODULE 2 |
| POST | `/api/generate-lesson` | ✅ Working | MODULE 7 |
| POST | `/api/modules/:id/regenerate` | ✅ Working | MODULE 2 |
| DELETE | `/api/paths/:id` | ✅ Working | MODULE 1 |
| POST | `/api/generate-quiz` | 🔲 To Build | MODULE 3 |
| POST | `/api/adaptive-recommendation` | 🔲 To Build | MODULE 4 |
| POST | `/api/progress-insights` | 🔲 To Build | MODULE 5 |

---

## Changelog

| Date | Change |
|---|---|
| 2026-05-13 | Project initialized — CogniPath + Moodle imported |
| 2026-05-13 | CogniPath: Firebase auth + Firestore removed — replaced with localStorage + local auto-login |
| 2026-05-13 | CogniPath: Backend Firebase dependency removed — routes work without any DB |
| 2026-05-13 | Full repo pushed to github.com/Tehman700/hackathonBahria |
| 2026-05-13 | **Strategy updated: Moodle is now the main LMS. CogniPath AI features will be integrated into Moodle as plugins.** |
| 2026-05-13 | Docker Compose setup created for Moodle 5.2 (PHP 8.2 + Apache + MySQL 8.0) |
