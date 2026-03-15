# API de Plataforma Educativa - Laravel

Esta es una API REST completa construida con Laravel para gestionar una plataforma educativa con instructores, cursos, lecciones, videos, comentarios y calificaciones.

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

### 2. CRUD Completo para Cursos

#### Endpoints de Cursos:
- `GET /api/v1/courses` - Listar todos los cursos (paginado)
- `POST /api/v1/courses` - Crear nuevo curso
- `GET /api/v1/courses/{id}` - Obtener detalles del curso
- `PUT /api/v1/courses/{id}` - Actualizar curso
- `DELETE /api/v1/courses/{id}` - Eliminar curso
- `POST /api/v1/courses/{id}/favorite` - Marcar como favorito
- `DELETE /api/v1/courses/{id}/favorite` - Quitar de favoritos

#### Validaciones en Cursos:
```
- title: requerido, string, máx 255 caracteres
- instructor_id: requerido, debe existir en instructores
```

### 3. Recuperación Eficiente de Instructores

Se implementó una consulta optimizada para manejar millones de registros:

```php
// Usa cursor pagination para eficiencia de memoria
// Selecciona solo las columnas necesarias (id, name, email, bio)
// Soporta parametrización de resultados por página

GET /api/v1/instructors?per_page=50&cursor=...
```

**Características de optimización:**
- Cursor pagination: evita problemas de memoria con grandes datasets
- Selección de columnas específicas: reduce transferencia de datos
- Índices en base de datos: optimiza búsquedas

### 4. Servicio de Cálculo de Rating

Se implementó el servicio `CourseService` que calcula:

#### Métodos:
```php
getAverageRating(Course $course): float
// Retorna el promedio de calificaciones de un curso

getCourseStats(Course $course): array
// Retorna estadísticas completas del curso:
// - average_rating
// - total_ratings
// - total_comments
// - total_lessons
// - total_favorites

getTopRatedCourses(int $limit = 10): Collection
// Retorna los N cursos mejor calificados
```

### 5. Endpoints Adicionales

#### Instructores:
- `GET /api/v1/instructors` - Listar instructores (paginado)
- `POST /api/v1/instructors` - Crear instructor
- `GET /api/v1/instructors/{id}` - Obtener instructor con estadísticas
- `PUT /api/v1/instructors/{id}` - Actualizar instructor
- `DELETE /api/v1/instructors/{id}` - Eliminar instructor

#### Lecciones:
- `GET /api/v1/lessons` - Listar lecciones (filtrable por curso)
- `POST /api/v1/lessons` - Crear lección
- `GET /api/v1/lessons/{id}` - Obtener lección
- `PUT /api/v1/lessons/{id}` - Actualizar lección
- `DELETE /api/v1/lessons/{id}` - Eliminar lección

### 6. Base de Datos

#### Migraciones Creadas:
- `create_instructors_table`
- `create_courses_table`
- `create_lessons_table`
- `create_videos_table`
- `create_comments_table`
- `create_ratings_table`
- `create_course_user_favorites_table`

#### Estructura de Ejemplos:
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

# (Opcional) Llenar base de datos con datos de ejemplo
php artisan tinker
# Dentro de tinker, ejecutar comandos de creación
```

## Ejemplos de Uso

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

### Obtener Estadísticas de un Curso
```bash
curl -X GET http://localhost:8000/api/v1/courses/1
# Retorna el curso con average_rating calculado
```

### Listar Instructores Eficientemente
```bash
# Primera página
curl -X GET "http://localhost:8000/api/v1/instructors?per_page=50"

# Página siguiente (usando cursor)
curl -X GET "http://localhost:8000/api/v1/instructors?per_page=50&cursor=EYJPZCI6NTB9"
```

### Marcar Curso como Favorito
```bash
curl -X POST http://localhost:8000/api/v1/courses/1/favorite \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1}'
```

## Consideraciones de Optimización

### Para Millones de Registros:

1. **Cursor Pagination**: Utiliza el endpoint de instructores con cursor pagination
   - Evita problemas de memoria con OFFSET/LIMIT
   - Eficiente para datasets muy grandes

2. **Selección de Columnas**: Solo se seleccionan columnas necesarias
   ```php
   Instructor::select('id', 'name', 'email', 'bio')
   ```

3. **Índices de Base de Datos**: Las relaciones foráneas crear índices automáticamente

4. **Eager Loading**: Se usa `with()` para evitar N+1 queries:
   ```php
   Course::with(['instructor', 'ratings', 'comments'])
   ```

5. **Agregaciones Eficientes**: Se usan funciones de base de datos:
   ```php
   $course->ratings()->avg('score')  // SELECT AVG
   $course->ratings()->count()        // SELECT COUNT
   ```

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
│   ├── Http/
│   │   └── Controllers/
│   │       ├── CourseController.php
│   │       ├── InstructorController.php
│   │       └── LessonController.php
│   └── Services/
│       └── CourseService.php
├── database/
│   └── migrations/
│       ├── create_instructors_table
│       ├── create_courses_table
│       ├── create_lessons_table
│       ├── create_videos_table
│       ├── create_comments_table
│       ├── create_ratings_table
│       └── create_course_user_favorites_table
├── routes/
│   └── api.php
└── bootstrap/
    └── app.php
```

## Notas Importantes

- No se requiere autenticación en las peticiones (como se especificó)
- Las relaciones polimórficas permiten comentarios y calificaciones tanto en cursos como en instructores
- El campo `unique` en ratings previene múltiples calificaciones del mismo usuario para el mismo objeto
- Se utiliza `cascade delete` para mantener la integridad referencial

## Testing

Para probar la API, se proporcionó datos de ejemplo que incluyen:
- 2 Instructores
- 2 Cursos
- 4 Lecciones con videos
- Calificaciones y comentarios
- Favoritos de cursos

Puedes verificar el funcionamiento accediendo a los endpoints mencionados arriba.
