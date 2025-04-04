require('dotenv').config();
const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const OpenAI = require('openai');

const app = express();
const port = process.env.PORT || 3000;

// Инициализация OpenAI
const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY
});

// Создаем подключение к базе данных
const db = new sqlite3.Database('events.db');

// Создаем таблицу событий, если она не существует
db.run(`
  CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    date TEXT NOT NULL,
    category TEXT NOT NULL,
    description TEXT,
    ticket_link TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  )
`);

// Middleware для обработки JSON
app.use(express.json());

// Раздача статических файлов
app.use(express.static(path.join(__dirname)));

// API endpoint для получения событий
app.get('/api/events', (req, res) => {
  db.all('SELECT * FROM events ORDER BY date ASC', (err, rows) => {
    if (err) {
      res.status(500).json({ error: err.message });
      return;
    }
    res.json(rows);
  });
});

// API endpoint для обновления событий
app.post('/api/events/update', async (req, res) => {
  try {
    const completion = await openai.chat.completions.create({
      model: "gpt-4-turbo-preview",
      messages: [
        {
          role: "system",
          content: "Ты - помощник по поиску событий в Санкт-Петербурге. Твоя задача - найти интересные мероприятия в апреле 2025 года и вернуть их в формате CSV. ВАЖНО: верни ТОЛЬКО CSV текст, без дополнительного текста. Первая строка должна содержать заголовки: name,date,category,description,ticket_link. Каждая следующая строка должна содержать данные события, разделенные запятыми. Если в поле есть запятые, заключи его в кавычки."
        },
        {
          role: "user",
          content: "Найди 5 интересных мероприятий в Санкт-Петербурге в апреле 2025 года и верни их в CSV формате."
        }
      ],
      temperature: 0.7
    });

    const response = completion.choices[0].message.content;
    console.log('Raw response:', response);

    // Разбиваем ответ на строки и удаляем пустые строки
    const lines = response.trim().split('\n').filter(line => line.trim());
    
    // Пропускаем заголовок
    const events = lines.slice(1).map(line => {
      const [name, date, category, description, ticket_link] = line.split(',').map(field => 
        field.trim().replace(/^"|"$/g, '') // Удаляем кавычки, если они есть
      );
      return { name, date, category, description, ticket_link };
    });

    // Очищаем старые события
    db.run('DELETE FROM events', (err) => {
      if (err) {
        res.status(500).json({ error: err.message });
        return;
      }

      // Добавляем новые события
      const stmt = db.prepare(`
        INSERT INTO events (name, date, category, description, ticket_link)
        VALUES (?, ?, ?, ?, ?)
      `);

      events.forEach(event => {
        if (event.name && event.date && event.category) {
          stmt.run(
            event.name,
            event.date,
            event.category,
            event.description || '',
            event.ticket_link || ''
          );
        }
      });

      stmt.finalize();
      res.json({ success: true, message: 'Events updated successfully' });
    });
  } catch (error) {
    console.error('Error updating events:', error);
    res.status(500).json({ error: error.message });
  }
});

// Запуск сервера
app.listen(port, () => {
  console.log(`Server is running on port ${port}`);
}); 