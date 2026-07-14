{
  "status": "success",
  "code": 200,
  "timestamp": "2024-01-15 10:30:00",
  "metadata": {
    "total_records": 45,
    "returned_records": 20,
    "filters": {
      "student_id": 1,
      "term_id": null,
      "subject_id": null,
      "limit": 20,
      "offset": 0
    },
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total_pages": 3,
      "total_records": 45
    },
    "statistics": {
      "total_records": 45,
      "average_score": 72.5,
      "highest_score": 95,
      "lowest_score": 45,
      "total_students": 1,
      "total_subjects": 8,
      "total_terms": 3
    },
    "grade_distribution": {
      "A": 12,
      "B": 15,
      "C": 10,
      "D": 6,
      "F": 2
    }
  },
  "data": [
    {
      "student_id": 1,
      "reg_no": "STU001",
      "student_name": "John Doe",
      "class": "Form 4A",
      "student_email": "john@example.com",
      "subject_id": 5,
      "subject_code": "MATH101",
      "subject_name": "Mathematics",
      "term_id": 2,
      "term_name": "Term 2",
      "year": "2024",
      "mark_id": 123,
      "score": 85,
      "recorded_at": "2024-06-15 14:30:00",
      "updated_at": "2024-06-15 14:30:00",
      "grade": "A",
      "gpa_points": 4.0,
      "score_percentage": "85%",
      "status": "PASS",
      "recorded_at_formatted": "2024-06-15 14:30:00",
      "updated_at_formatted": "2024-06-15 14:30:00",
      "grade_color": "#22c55e"
    }
  ]
}