# AtomCamp Smart Adaptive LMS — Project Progress

## What We're Building

A **unified Smart Adaptive LMS** for AtomCamp (AUREX 26 Hackathon).

We started with two open-source projects:
- **CogniPath** — AI-powered personalized learning path generator (React + Flask + Gemini)
- **Moodle** — Full-featured LMS reference (PHP, not running separately)

**Merge strategy:** CogniPath is the live application. We are building all missing LMS features (quizzes, analytics, instructor dashboard, adaptive engine) **directly into CogniPath**, using Moodle's architecture as the feature reference. Moodle is NOT run as a separate server.

---

## Tech Stack (Unified App)

| Layer | Technology |
|---|---|
| Frontend | React 19 + TypeScript + Vite + Tailwind CSS |
| Backend | Python Flask + Gunicorn |
| AI Engine | Google Gemini 2.5 Flash (path gen, chat, quizzes) |
| Storage | localStorage (browser) — no DB required to run |
| Auth | Local auto-login (no Firebase needed) |
| Routing | React Router DOM v7 |

---

## Run Commands

### Prerequisites
- Node.js v18+
- Python 3.9+
- A **Gemini API Key** from https://aistudio.google.com/apikey

### Step 1 — Add your Gemini API key
Edit `CogniPath/CogniPathLMS/server/.env`:
```
GOOGLE_API_KEY=your_key_here
PORT=8080
```

### Step 2 — Start the Backend
```powershell
cd "d:\LMS Hackathon\CogniPath\CogniPathLMS\server"
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
python run.py
# Backend running at http://localhost:8080
```

### Step 3 — Start the Frontend
```powershell
cd "d:\LMS Hackathon\CogniPath\CogniPathLMS\client"
npm install
npm run dev
# Frontend running at http://localhost:5173
```

Open **http://localhost:5173** — you are auto-logged in as "Learner", no sign-in needed.

---

## Modules Status

### DONE — Carried Over from CogniPath

| Module | Description | Status |
|---|---|---|
| Firebase Removal | Replaced Firebase auth + Firestore with local auto-login + localStorage | ✅ Done |
| AI Learning Path Generator | Gemini generates personalized curriculum from goal + level + uploaded files | ✅ Done |
| Lesson Content Generator | Gemini writes full markdown lesson per module | ✅ Done |
| Lesson Regeneration | Regenerate any lesson with user feedback | ✅ Done |
| Socratic AI Tutor Chat | Context-aware chat per module using Socratic method | ✅ Done |
| File Upload Context | PDF, DOCX, TXT files used to enrich AI-generated paths | ✅ Done |
| Dashboard | Grid of user's saved learning paths | ✅ Done |
| Module Viewer | Read lessons, navigate prev/next, progress bar | ✅ Done |
| Persistent Storage | All paths, modules, chat messages saved to localStorage | ✅ Done |
| Dark Mode | Theme toggle throughout app | ✅ Done |

---

### TO BUILD — New Modules

#### MODULE 1 — Quiz & Assessment System
**Priority: HIGH**
- After each lesson, Gemini auto-generates 5 MCQ questions based on lesson content
- User answers and gets instant scored results (0–100%)
- Results stored in localStorage per module
- Pass threshold: 70% — shows "Passed" or "Needs Review"
- **Files to create:**
  - `client/src/pages/Quiz.tsx`
  - `client/src/components/features/Quiz/QuizCard.tsx`
  - `client/src/components/features/Quiz/QuizResult.tsx`
  - `server/app/routes/agent_routes.py` — add `/generate-quiz` endpoint
  - `server/app/services/gemini_service.py` — add `generate_quiz()` function
- **Status:** Not started

---

#### MODULE 2 — Progress Tracking & Analytics
**Priority: HIGH**
- Track per-module: completion status, quiz score, time spent, last accessed
- Student dashboard shows: overall completion %, average quiz score, modules passed/failed
- Visual progress ring/bar on dashboard cards
- **Files to modify:**
  - `client/src/types/gemini.ts` — extend `ModuleData` with `quizScore`, `timeSpent`, `completedAt`
  - `client/src/pages/Dashboard.tsx` — add progress indicators
  - `client/src/hooks/useGemini.ts` — add `getProgressSummary()` helper
  - `client/src/components/features/ProgressRing.tsx` — new component
- **Status:** Not started

---

#### MODULE 3 — Adaptive Difficulty Engine
**Priority: HIGH**
- After each quiz, system adjusts recommendations:
  - Score < 50%: suggest reviewing current module again, slower pace
  - Score 50–79%: proceed normally
  - Score ≥ 80%: offer to skip ahead or go deeper
- Inject quiz history into Gemini's lesson generation prompt for adaptive content
- **Files to modify:**
  - `server/app/services/gemini_service.py` — pass quiz performance context
  - `server/app/prompts/system_prompts.py` — update prompts with adaptive instructions
  - `client/src/pages/ModuleDetail.tsx` — show adaptive recommendation after quiz
- **Status:** Not started

---

#### MODULE 4 — Instructor / Admin Dashboard
**Priority: MEDIUM**
- Multiple learner profiles (add/switch learners in local mode)
- Instructor sees: all learners, each learner's paths, progress, quiz scores
- Flag struggling learners (< 60% average)
- Exportable progress summary
- **Files to create:**
  - `client/src/pages/InstructorDashboard.tsx`
  - `client/src/components/features/LearnerCard.tsx`
  - `client/src/context/MultiUserContext.tsx` — manage multiple local profiles
- **Status:** Not started

---

#### MODULE 5 — Certificate of Completion
**Priority: MEDIUM**
- When all modules in a path have quiz score ≥ 70%, path is "completed"
- Generate a printable certificate card with: learner name, path title, date, score
- Download as PNG/PDF via browser print
- **Files to create:**
  - `client/src/pages/Certificate.tsx`
  - `client/src/components/features/CertificateCard.tsx`
- **Status:** Not started

---

#### MODULE 6 — AtomCamp Course Catalog
**Priority: MEDIUM**
- Pre-built learning path templates based on AtomCamp's real programs (AI, Web Dev, etc.)
- User can start from a template instead of typing a goal from scratch
- Templates are editable/forkable
- **Files to create:**
  - `client/src/pages/Catalog.tsx`
  - `client/src/data/atomcamp_templates.ts` — hardcoded AtomCamp program data
- **Status:** Not started

---

#### MODULE 7 — Enhanced AI Tutor (Moodle-Inspired)
**Priority: LOW**
- Forum-style Q&A: questions saved and searchable across sessions
- Suggested follow-up questions after each AI response
- Inline code execution hints (no sandbox, just show commands)
- **Files to modify:**
  - `client/src/components/features/ChatBot/` — add suggestions, Q&A log
- **Status:** Not started

---

## Data Model (localStorage)

```
lms_paths          → Array of all saved paths (with userId, id, createdAt)
lms_module_{userId}_{pathId}_{moduleId}  → Module data (content, status, quizScore, timeSpent)
lms_msgs_{userId}_{pathId}_{moduleId}    → Chat messages array
lms_quiz_{userId}_{pathId}_{moduleId}    → Quiz questions + user answers + score
lms_learners       → Array of learner profiles (for instructor mode)
```

---

## API Endpoints

| Method | Endpoint | Status |
|---|---|---|
| POST | `/api/generate-path` | ✅ Working |
| POST | `/api/chat` | ✅ Working |
| POST | `/api/generate-lesson` | ✅ Working |
| POST | `/api/modules/:id/regenerate` | ✅ Working |
| DELETE | `/api/paths/:id` | ✅ Working |
| POST | `/api/generate-quiz` | 🔲 To Build |

---

## Changelog

| Date | Change |
|---|---|
| 2026-05-13 | Project initialized — CogniPath + Moodle imported |
| 2026-05-13 | Firebase auth + Firestore removed — replaced with localStorage + local auto-login |
| 2026-05-13 | Backend Firebase dependency removed — routes work without any DB |
| 2026-05-13 | `.env` files created — only Gemini API key required to run |
| 2026-05-13 | Full repo pushed to github.com/Tehman700/hackathonBahria |
