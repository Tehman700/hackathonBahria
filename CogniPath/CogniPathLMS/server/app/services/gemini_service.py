import os
import json
import io
from openai import OpenAI
from pypdf import PdfReader
from docx import Document
from dotenv import load_dotenv
from app.prompts.system_prompts import (
    SOCRATIC_TUTOR_PROMPT,
    PATH_GENERATOR_PROMPT,
    LESSON_GENERATOR_PROMPT,
    HIERARCHICAL_CHAT_PROMPT,
    QUIZ_GENERATOR_PROMPT,
    ADAPTIVE_PROMPT,
    INSIGHTS_PROMPT,
)

load_dotenv()

client = OpenAI(api_key=os.environ.get("OPENAI_API_KEY"))

# Models
PATH_MODEL = "gpt-4o"          # structured JSON output — needs strongest reasoning
CHAT_MODEL = "gpt-4o-mini"     # real-time chat — fast and cheap
LESSON_MODEL = "gpt-4o-mini"   # long-form writing
QUIZ_MODEL = "gpt-4o"          # structured JSON output


def extract_text_from_file(file_storage):
    filename = file_storage.filename.lower()
    text = ""
    try:
        if filename.endswith(".pdf"):
            reader = PdfReader(file_storage)
            for page in reader.pages:
                text += page.extract_text() + "\n"
        elif filename.endswith(".docx"):
            doc = Document(file_storage)
            for para in doc.paragraphs:
                text += para.text + "\n"
        elif filename.endswith(".txt") or filename.endswith(".md"):
            text = file_storage.read().decode("utf-8", errors="ignore")
            file_storage.seek(0)
    except Exception as e:
        print(f"Error extracting text from {filename}: {e}")
        return f"[Error reading file {filename}]"
    return text


def generate_path(profile, files=None):
    try:
        context_text = ""
        if files:
            context_text = "\n\nCONTEXT FROM UPLOADED FILES:\n"
            for file in files:
                file_text = extract_text_from_file(file)
                if len(file_text) > 100000:
                    file_text = file_text[:100000] + "...[truncated]"
                context_text += f"\n--- {file.filename} ---\n{file_text}\n"

        user_prompt = f"""
Create a personalized learning path for:
Student Name: {profile.get('name', 'Student')}
Goal: {profile.get('goal', 'General improvement')}
Current Level: {profile.get('level', 'Beginner')}
{context_text}

Tailor the curriculum to the goal above. If file context is provided, incorporate its concepts directly into the module topics.
"""

        response = client.chat.completions.create(
            model=PATH_MODEL,
            response_format={"type": "json_object"},
            messages=[
                {"role": "system", "content": PATH_GENERATOR_PROMPT},
                {"role": "user", "content": user_prompt},
            ],
        )

        return json.loads(response.choices[0].message.content)

    except Exception as e:
        print(f"Error generating path: {e}")
        return {"error": str(e)}


def generate_lesson_content(topic, description, user_goal, feedback=None):
    try:
        feedback_text = ""
        if feedback and isinstance(feedback, list) and len(feedback) > 0:
            feedback_text = "\n\nIMPORTANT — address these user feedback points in the new content:\n"
            for item in feedback:
                feedback_text += f"- {item}\n"

        prompt = f"""
Topic: {topic}
Description: {description}
Student Goal: {user_goal}
{feedback_text}

Write the full lesson content now.
"""

        response = client.chat.completions.create(
            model=LESSON_MODEL,
            messages=[
                {"role": "system", "content": LESSON_GENERATOR_PROMPT},
                {"role": "user", "content": prompt},
            ],
            temperature=0.7,
        )

        return response.choices[0].message.content

    except Exception as e:
        print(f"Error generating lesson: {e}")
        return f"# Error Generating Lesson\n\nSorry, an error occurred: {str(e)}"


def generate_quiz(topic, lesson_content, user_goal):
    try:
        prompt = f"""
Topic: {topic}
Student Goal: {user_goal}

Lesson Content (use this to base your questions on):
{lesson_content[:8000] if lesson_content else 'No content provided — generate questions based on the topic.'}

Generate 5 multiple-choice questions that test comprehension of the above content.
"""

        response = client.chat.completions.create(
            model=QUIZ_MODEL,
            response_format={"type": "json_object"},
            messages=[
                {"role": "system", "content": QUIZ_GENERATOR_PROMPT},
                {"role": "user", "content": prompt},
            ],
        )

        return json.loads(response.choices[0].message.content)

    except Exception as e:
        print(f"Error generating quiz: {e}")
        return {"error": str(e)}


def get_adaptive_recommendation(topic, score, total_questions):
    try:
        percentage = round((score / total_questions) * 100)

        if percentage >= 80:
            performance = "excellent"
            direction = "suggest moving to the next module or exploring advanced content"
        elif percentage >= 60:
            performance = "satisfactory"
            direction = "suggest proceeding normally but reviewing any weak areas"
        else:
            performance = "struggling"
            direction = "recommend revisiting the lesson before proceeding"

        prompt = f"""
Student just completed a quiz on: {topic}
Score: {score}/{total_questions} ({percentage}%)
Performance level: {performance}
Recommended direction: {direction}

Write a personalized recommendation message for this student.
"""

        response = client.chat.completions.create(
            model=CHAT_MODEL,
            messages=[
                {"role": "system", "content": ADAPTIVE_PROMPT},
                {"role": "user", "content": prompt},
            ],
            temperature=0.6,
        )

        return {
            "score": score,
            "total": total_questions,
            "percentage": percentage,
            "recommendation": response.choices[0].message.content,
            "action": "advance" if percentage >= 80 else "review" if percentage < 60 else "proceed",
        }

    except Exception as e:
        print(f"Error generating recommendation: {e}")
        return {"error": str(e)}


def get_progress_insights(class_data):
    try:
        prompt = f"""
Class performance data:
{json.dumps(class_data, indent=2)}

Generate a brief instructor insight report based on this data.
"""

        response = client.chat.completions.create(
            model=CHAT_MODEL,
            messages=[
                {"role": "system", "content": INSIGHTS_PROMPT},
                {"role": "user", "content": prompt},
            ],
            temperature=0.5,
        )

        return response.choices[0].message.content

    except Exception as e:
        print(f"Error generating insights: {e}")
        return f"Error generating insights: {str(e)}"


def chat(history, message, context_data=None):
    try:
        system_instruction = HIERARCHICAL_CHAT_PROMPT

        if context_data:
            if context_data.get("moduleContent"):
                system_instruction += f"\n\n--- CURRENT MODULE CONTENT ---\n{context_data['moduleContent'][:20000]}"
            if context_data.get("pathSyllabus"):
                system_instruction += f"\n\n--- PATH SYLLABUS ---\n{context_data['pathSyllabus']}"

        messages = [{"role": "system", "content": system_instruction}]

        for msg in history:
            role = "user" if msg["role"] == "user" else "assistant"
            messages.append({"role": role, "content": msg["text"]})

        messages.append({"role": "user", "content": message})

        response = client.chat.completions.create(
            model=CHAT_MODEL,
            messages=messages,
            temperature=0.7,
        )

        return response.choices[0].message.content

    except Exception as e:
        print(f"Error in chat: {e}")
        return f"Error: {str(e)}"
