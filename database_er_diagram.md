# Gym Management System - ER Diagram

```mermaid
erDiagram
    USERS ||--o{ TRAINERS : has
    USERS ||--o{ CLIENTS : has
    TRAINERS ||--o{ CLIENTS : manages
    TRAINERS ||--o{ WORKOUT_PLANS : creates
    TRAINERS ||--o{ DIET_PLANS : creates
    CLIENTS ||--o{ WORKOUT_PLANS : follows
    CLIENTS ||--o{ DIET_PLANS : follows
    CLIENTS ||--o{ PROGRESS_TRACKING : tracks
    WORKOUT_PLANS ||--o{ WORKOUT_EXERCISES : contains
    DIET_PLANS ||--o{ DIET_MEALS : contains

    USERS {
        int id PK
        string username
        string password
        string email
        enum user_type
        timestamp created_at
        timestamp updated_at
    }

    TRAINERS {
        int id PK
        int user_id FK
        string specialization
        text bio
        int experience_years
    }

    CLIENTS {
        int id PK
        int user_id FK
        int trainer_id FK
        decimal height
        decimal target_weight
        text health_conditions
    }

    WORKOUT_PLANS {
        int id PK
        int trainer_id FK
        int client_id FK
        string name
        text description
        date start_date
        date end_date
    }

    WORKOUT_EXERCISES {
        int id PK
        int plan_id FK
        string exercise_name
        int sets
        int reps
        enum day_of_week
        text notes
    }

    DIET_PLANS {
        int id PK
        int trainer_id FK
        int client_id FK
        string name
        text description
        int total_calories
        date start_date
        date end_date
    }

    DIET_MEALS {
        int id PK
        int plan_id FK
        string meal_name
        int calories
        decimal proteins
        decimal carbs
        decimal fats
        time time_of_day
    }

    PROGRESS_TRACKING {
        int id PK
        int client_id FK
        date date
        decimal weight
        decimal body_fat
        json measurements
        string photo_url
    }
```

## Relationship Details

1. Users can be either Trainers or Clients (1:1)
2. Trainers can manage multiple Clients (1:N)
3. Trainers can create multiple Workout Plans and Diet Plans (1:N)
4. Clients can have multiple Workout Plans and Diet Plans (1:N)
5. Workout Plans contain multiple Exercises (1:N)
6. Diet Plans contain multiple Meals (1:N)
7. Clients can have multiple Progress Tracking entries (1:N)

## Key Features
- Role-based user system (Trainers and Clients)
- Comprehensive workout and diet planning
- Detailed progress tracking with measurements and photos
- Flexible meal and exercise scheduling
- Complete client management for trainers
