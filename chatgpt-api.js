async function getEventsFromChatGPT() {
    try {
        const response = await fetch('https://api.openai.com/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${OPENAI_API_KEY}`
            },
            body: JSON.stringify({
                model: "gpt-4-turbo-preview",
                messages: [
                    {
                        role: "system",
                        content: "Ты - помощник по поиску событий в Санкт-Петербурге. Твоя задача - найти 2 интересных мероприятия в апреле 2025 года и вернуть их в формате CSV. ВАЖНО: верни ТОЛЬКО CSV текст, без дополнительного текста. Первая строка должна содержать заголовки: название,дата,категория,описание. Каждая следующая строка должна содержать данные события, разделенные запятыми. Если в поле есть запятые, заключи его в кавычки."
                    },
                    {
                        role: "user",
                        content: "Найди 2 интересных мероприятия в Санкт-Петербурге в апреле 2025 года и верни их в CSV формате."
                    }
                ],
                temperature: 0.7
            })
        });

        if (!response.ok) {
            throw new Error('Ошибка при запросе к ChatGPT API');
        }

        const data = await response.json();
        const csvText = data.choices[0].message.content;
        
        // Разбиваем CSV на строки
        const lines = csvText.trim().split('\n').filter(line => line.trim());
        
        // Пропускаем заголовок
        const events = lines.slice(1).map(line => {
            const [name, date, category, description] = line.split(',').map(field => 
                field.trim().replace(/^"|"$/g, '') // Удаляем кавычки, если они есть
            );
            return { name, date, category, description };
        });

        return events;
    } catch (error) {
        console.error('Error getting events from ChatGPT:', error);
        throw error;
    }
} 