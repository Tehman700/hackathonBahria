# Atomcamp Smart Adaptive LMS

Moodle is the main LMS. CogniPath code is used only as integrated AI backend modules.

## Main functionalities
- Student signs up and completes an onboarding survey (interests, goals, current skills).
- AI uses onboarding answers to generate a personalized learning path.
- Student can open AI Tutor chat in courses (Socratic guidance style).
- Instructor can generate quiz drafts from lesson content.
- Moodle core LMS features remain available:
  - course management
  - quizzes and grading
  - progress tracking
  - roles (student/teacher/admin)
  - enrollment and scheduling

## Run the project (local)
1. Start Moodle
   - `cd /home/runner/work/hackathonBahria/hackathonBahria/moodle_LMS`
   - `docker compose up -d --build`
2. Open Moodle at `http://localhost:8090` and complete installer.
   - DB host: `moodledb`
   - DB name: `moodle`
   - DB user: `moodle`
   - DB password: `moodle123`
   - Moodle data dir: `/var/moodledata`
3. Start AI backend
   - `cd /home/runner/work/hackathonBahria/hackathonBahria/CogniPath/CogniPathLMS/server`
   - `python -m venv venv`
   - `source venv/bin/activate` (Windows: `venv\\Scripts\\activate`)
   - `pip install -r requirements.txt`
   - create `.env` with:
     - `OPENAI_API_KEY=your_key`
     - `PORT=8080`
   - `python run.py`
4. In Moodle, install/upgrade local plugins when prompted (Site administration → Notifications).
5. Use integrated features:
   - user signup flow includes onboarding survey
   - AI Learning Path page: `/local/ai_pathgen/index.php`
   - AI Tutor block in courses
   - AI Quiz Generator page: `/local/ai_quizgen/index.php`
