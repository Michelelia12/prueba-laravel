# API de Plataforma Educativa - Laravel

Esta es una API REST completa construida con Laravel para gestionar una plataforma educativa con instructores, cursos, lecciones, videos, comentarios y calificaciones. Incluye optimizaciones para manejar grandes volúmenes de datos y está completamente cubierta por tests.

## Características Implementadas

### 1. Modelos y Relaciones

#### Modelos Base:
- **Instructor**: Profesor que imparte cursos
- **Course**: Cursos impartidos por instructores
- **Lesson**: Lecciones dentro de un curso
- **Video**: Videos asociados a las lecciones
- **User**: Usuarios de la plataforma
- **Comment**: Comentarios en cursos e instructores (relación polimórfica)
- **Rating**: Calificaciones de cursos e instructores (relación polimórfica)

#### Relaciones:
```
Instructor (1) -> (Many) Courses
Course (1) -> (Many) Lessons
Lesson (1) -> (One) Video
User (Many) -> (Many) Courses (Favoritos)
User (1) -> (Many) Comments
User (1) -> (Many) Ratings
Course (Polimórfico) <- Comments
Instructor (Polimórfico) <- Comments
Course (Polimórfico) <- Ratings
Instructor (Polimórfico) <- Ratings
```

### 2. CRUD Completo para Todas las Entidades

#### Endpoints de Cursos:
- `GET /api/v1/courses` - Listar todos los cursos (paginado)
- `POST /api/v1/courses` - Crear nuevo curso
- `GET /api/v1/courses/{id}` - Obtener detalles del curso con estadísticas
- `PUT /api/v1/courses/{id}` - Actualizar curso
- `DELETE /api/v1/courses/{id}` - Eliminar curso
- `POST /api/v1/courses/{id}/favorite` - Marcar como favorito (requiere autenticación)
- `DELETE /api/v1/courses/{id}/favorite` - Quitar de favoritos (requiere autenticación)

#### Validaciones en Cursos:
```
title: requerido, string, máx 255 caracteres
description: requerido, string
instructor_id: requerido, debe existir en instructores
price: opcional, numérico, mínimo 0
level: opcional, debe ser: beginner, intermediate, advanced
```

#### Endpoints de Instructores:
- `GET /api/v1/instructors` - Listar instructores (paginado)
- `POST /api/v1/instructors` - Crear instructor
- `GET /api/v1/instructors/{id}` - Obtener instructor con estadísticas
- `PUT /api/v1/instructors/{id}` - Actualizar instructor
- `DELETE /api/v1/instructors/{id}` - Eliminar instructor

#### Validaciones en Instructores:
```
name: requerido, string, máx 255 caracteres
email: requerido, email único
bio: opcional, string
avatar: opcional, string
```

#### Endpoints de Lecciones:
- `GET /api/v1/lessons` - Listar lecciones (paginado, filtrable por course_id)
- `POST /api/v1/lessons` - Crear lección
- `GET /api/v1/lessons/{id}` - Obtener lección con relaciones
- `PUT /api/v1/lessons/{id}` - Actualizar lección
- `DELETE /api/v1/lessons/{id}` - Eliminar lección

#### Validaciones en Lecciones:
```
title: requerido, string, máx 255 caracteres
description: opcional, string
course_id: requerido, debe existir en cursos
sequence: requerido, entero, mínimo 0
```

### 3. Recuperación Eficiente de Instructores

Se implementó una consulta optimizada para manejar millones de registros:

```php
// Endpoint especial para recuperación masiva de instructores
GET /api/v1/courses/instructors?per_page=50&cursor=...
```

**Características de optimización:**
- Cursor pagination: evita problemas de memoria con grandes datasets
- Selección de columnas específicas: reduce transferencia de datos
- Sin eager loading innecesario
- Memoria constante independientemente del tamaño del dataset

### 4. Servicio de Cálculo de Estadísticas

Se implementó el servicio `CourseService` con métodos optimizados:

#### Métodos:
```php
getAverageRating(Course $course): float
// Calcula el promedio de calificaciones usando AVG de base de datos

getAverageRating(Instructor $instructor): float
// Calcula el promedio de calificaciones del instructor
```

### 5. Endpoints Adicionales

#### Health Check:
- `GET /health` - Verificar estado de la aplicación

### 6. Base de Datos

#### Migraciones Creadas:
- `create_instructors_table`
- `create_courses_table`
- `create_lessons_table`
- `create_videos_table`
- `create_comments_table`
- `create_ratings_table`
- `create_course_user_favorites_table`

#### Estructura de Tablas:
```sql
-- Instructores
id, name, email, bio, avatar, created_at, updated_at

-- Cursos
id, title, description, instructor_id, price, level, created_at, updated_at

-- Lecciones
id, title, description, course_id, sequence, created_at, updated_at

-- Videos
id, title, url, duration, lesson_id, created_at, updated_at

-- Comentarios (Polimórfico)
id, content, user_id, commentable_id, commentable_type, created_at, updated_at

-- Calificaciones (Polimórfico)
id, score, user_id, ratable_id, ratable_type, created_at, updated_at
(Unique: user_id, ratable_id, ratable_type)

-- Favoritos
id, user_id, course_id, created_at, updated_at
(Unique: user_id, course_id)
```

## Instalación y Configuración

```bash
# Instalar dependencias
composer install

# Configurar archivo .env
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# (Opcional) Ejecutar seeders para datos de prueba
php artisan db:seed
```

## Ejemplos de Uso

### Health Check
```bash
curl -X GET http://localhost:8000/health
# Response: {"status": "ok"}
```

### Crear un Instructor
```bash
curl -X POST http://localhost:8000/api/v1/instructors \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "bio": "Experto en desarrollo web",
    "avatar": "https://example.com/avatar.jpg"
  }'
```

### Crear un Curso
```bash
curl -X POST http://localhost:8000/api/v1/courses \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Domina Laravel",
    "description": "Aprende Laravel desde cero hasta avanzado",
    "instructor_id": 1,
    "price": 99.99,
    "level": "intermediate"
  }'
```

### Obtener un Curso con Estadísticas
```bash
curl -X GET http://localhost:8000/api/v1/courses/1
# Response incluye: course data + average_rating
```

### Listar Instructores Eficientemente
```bash
# Primera página
curl -X GET "http://localhost:8000/api/v1/courses/instructors?per_page=50"

# Página siguiente (usando cursor)
curl -X GET "http://localhost:8000/api/v1/courses/instructors?cursor=eyJpZCI6NTB9"
```

### Marcar Curso como Favorito
```bash
# Requiere autenticación (Bearer token)
curl -X POST http://localhost:8000/api/v1/courses/1/favorite \
  -H "Authorization: Bearer {token}"
```

### Filtrar Lecciones por Curso
```bash
curl -X GET "http://localhost:8000/api/v1/lessons?course_id=1"
```

## Testing

La aplicación incluye cobertura completa de tests (100%):

```bash
# Ejecutar todos los tests
./vendor/bin/phpunit

# Ejecutar tests con cobertura
./vendor/bin/phpunit --coverage-html=reports

# Ejecutar tests específicos
./vendor/bin/phpunit tests/Feature/Routes/ApiTest.php
./vendor/bin/phpunit tests/Feature/Models/
```

### Suites de Test Incluidas:
- **ApiTest**: Tests completos de API para cursos, lecciones, instructores
- **InstructorApiTest**: CRUD completo para instructores
- **CourseTest**: Relaciones y lógica de negocio de cursos
- **UserTest**: Relaciones de usuarios
- **VideoTest, CommentTest, RatingTest**: Tests de modelos
- **CourseServiceTest**: Tests del servicio de estadísticas

## Consideraciones de Optimización

### Para Millones de Registros:

1. **Cursor Pagination**: Utilizado en `/api/v1/courses/instructors`
   - Evita problemas de memoria con OFFSET/LIMIT
   - Eficiente para datasets muy grandes

2. **Selección de Columnas**: Solo columnas necesarias en consultas masivas
   ```php
   Instructor::select('id', 'name', 'email', 'bio')
   ```

3. **Eager Loading Estratégico**: Se usa `with()` para evitar N+1:
   ```php
   Course::with(['instructor', 'lessons.video', 'ratings', 'comments'])
   ```

4. **Agregaciones Eficientes**: Funciones de base de datos:
   ```php
   $course->ratings()->avg('score')  // SELECT AVG(score)
   ```

5. **Índices Automáticos**: Foreign keys crean índices automáticamente

## Estructura del Proyecto

```
backend/
├── app/
│   ├── Models/
│   │   ├── Instructor.php
│   │   ├── Course.php
│   │   ├── Lesson.php
│   │   ├── Video.php
│   │   ├── Comment.php
│   │   ├── Rating.php
│   │   └── User.php
│   ├── Http/Controllers/
│   │   └── CourseController.php
│   └── Services/
│       └── CourseService.php
├── database/
│   ├── factories/
│   │   ├── InstructorFactory.php
│   │   ├── CourseFactory.php
│   │   ├── LessonFactory.php
│   │   ├── VideoFactory.php
│   │   ├── CommentFactory.php
│   │   ├── RatingFactory.php
│   │   └── UserFactory.php
│   └── migrations/
│       ├── create_instructors_table.php
│       ├── create_courses_table.php
│       ├── create_lessons_table.php
│       ├── create_videos_table.php
│       ├── create_comments_table.php
│       ├── create_ratings_table.php
│       └── create_course_user_favorites_table.php
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   │   ├── Models/
│   │   └── Routes/
│   │       ├── ApiTest.php
│   │       └── InstructorApiTest.php
│   └── Unit/
└── bootstrap/
    └── app.php
```

## Tecnologías Utilizadas

- **Laravel 12**: Framework PHP
- **PHP 8.2+**: Lenguaje de programación
- **SQLite/MySQL/PostgreSQL**: Base de datos
- **PHPUnit**: Testing framework
- **Larastan**: PHPStan para Laravel
- **Pint**: Code formatter

## Notas Importantes

- Autenticación requerida solo para endpoints de favoritos
- Relaciones polimórficas permiten comentarios/calificaciones en cursos e instructores
- Validaciones exhaustivas en todos los endpoints
- Cobertura de tests al 100%
- Optimizado para grandes volúmenes de datos
- Código compliant con PHPStan max level
