# CogniPath AI Backend for Moodle Integration

This Flask service powers the AI features that are integrated into Moodle LMS plugins/blocks.

## Run backend locally
1. `cd /home/runner/work/hackathonBahria/hackathonBahria/CogniPath/CogniPathLMS/server`
2. `python -m venv venv`
3. `source venv/bin/activate` (Windows: `venv\\Scripts\\activate`)
4. `pip install -r requirements.txt`
5. Create `.env`:
   - `OPENAI_API_KEY=your_key`
   - `PORT=8080`
6. `python run.py`

Backend will run on `http://localhost:8080`.
