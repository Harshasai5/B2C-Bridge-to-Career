import sys
import re
import os
import docx2txt
import PyPDF2
import mysql.connector

# Optional imports wrapped in try/except
try:
    import textstat
except:
    textstat = None

try:
    import language_tool_python
except:
    language_tool_python = None

# --- Database connection ---
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="b2c"
)
cursor = db.cursor(dictionary=True)

# --- Inputs from PHP ---
student_reg_no = sys.argv[1]
company_id = sys.argv[2]
resume_file = sys.argv[3]

# --- Extract text from resume ---
def extract_text(file_path):
    text = ""
    if file_path.lower().endswith(".pdf"):
        try:
            with open(file_path, "rb") as f:
                reader = PyPDF2.PdfReader(f)
                for page in reader.pages:
                    if page.extract_text():
                        text += page.extract_text() + " "
        except Exception as e:
            print("Error reading PDF:", e)
    elif file_path.lower().endswith(".docx"):
        try:
            text = docx2txt.process(file_path)
        except Exception as e:
            print("Error reading DOCX:", e)
    return text.lower()

resume_text = extract_text(resume_file)

# --- Company skills ---
cursor.execute("SELECT skills_required FROM companies WHERE company_id=%s", (company_id,))
company = cursor.fetchone()
if not company:
    print("Company not found")
    sys.exit(1)

skills = [s.strip().lower() for s in company["skills_required"].split(",") if s.strip()]

# --- Skill matching ---
matched = [s for s in skills if re.search(r"\b" + re.escape(s) + r"\b", resume_text)]
missing = [s for s in skills if s not in matched]

# --- ATS Score base ---
ats_score = int((len(matched) / len(skills)) * 100) if skills else 0

# --- Extra validations (safe fallback) ---
suggestions = []

# Readability
if textstat:
    try:
        readability = textstat.flesch_reading_ease(resume_text)
        if readability < 50:
            suggestions.append("Improve readability (shorter sentences, simpler words)")
    except:
        readability = 0
else:
    readability = 0

# Grammar
if language_tool_python:
    try:
        tool = language_tool_python.LanguageTool("en-US")
        issues = tool.check(resume_text[:3000])
        if issues:
            suggestions.append(f"Fix grammar issues (~{len(issues)})")
    except:
        issues = []
else:
    issues = []

# Suggestions for missing skills
if missing:
    suggestions.append("Add missing skills: " + ", ".join(missing))

final_suggestions = " | ".join(suggestions)

# --- Insert result ---
cursor.execute("""
    INSERT INTO ats_results (reg_no, company_id, ats_score, missing_keywords, suggestions)
    VALUES (%s, %s, %s, %s, %s)
""", (student_reg_no, company_id, ats_score, ", ".join(missing), final_suggestions))
db.commit()

# --- Output ---
print(f"ATS Score: {ats_score}%")
print(f"Matched Skills: {', '.join(matched) if matched else 'None'}")
print(f"Missing Skills: {', '.join(missing) if missing else 'None'}")
print(f"Readability Score: {readability}")
print(f"Grammar Issues Found: {len(issues)}")
print(f"Suggestions: {final_suggestions if final_suggestions else 'Looks good!'}")

cursor.close()
db.close()
