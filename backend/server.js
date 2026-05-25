const http = require("http");
const fs = require("fs");
const path = require("path");

const PORT = 5000;

const usersFile = path.join(__dirname, "users.json");

if (!fs.existsSync(usersFile)) {
  fs.writeFileSync(usersFile, "[]");
}

const server = http.createServer((req, res) => {
  // CORS
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, POST, PUT, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type");

  if (req.method === "OPTIONS") {
    res.writeHead(200);
    return res.end();
  }

  // POST
  if (req.method === "POST" && req.url === "/api/form") {
    let body = "";

    req.on("data", chunk => {
      body += chunk.toString();
    });

    req.on("end", () => {
      try {
        const data = JSON.parse(body);

        // Валидация
        if (!data.name || !data.phone || !data.email) {
          res.writeHead(400, {
            "Content-Type": "application/json",
          });

          return res.end(
            JSON.stringify({
              error: "Заполните обязательные поля",
            })
          );
        }

        const users = JSON.parse(fs.readFileSync(usersFile));

        const newUser = {
          id: Date.now(),
          login: "user" + Math.floor(Math.random() * 10000),
          password: Math.random().toString(36).slice(2, 10),
          profile: `/profile/${Date.now()}`,
          ...data,
        };

        users.push(newUser);

        fs.writeFileSync(usersFile, JSON.stringify(users, null, 2));

        res.writeHead(200, {
          "Content-Type": "application/json",
        });

        res.end(
          JSON.stringify({
            success: true,
            login: newUser.login,
            password: newUser.password,
            profile: newUser.profile,
          })
        );
      } catch (err) {
        res.writeHead(500, {
          "Content-Type": "application/json",
        });

        res.end(
          JSON.stringify({
            error: "Ошибка сервера",
          })
        );
      }
    });

    return;
  }

  // PUT редактирование
  if (req.method === "PUT" && req.url.startsWith("/api/form/")) {
    let body = "";

    req.on("data", chunk => {
      body += chunk.toString();
    });

    req.on("end", () => {
      try {
        const id = Number(req.url.split("/").pop());

        const updatedData = JSON.parse(body);

        const users = JSON.parse(fs.readFileSync(usersFile));

        const index = users.findIndex(user => user.id === id);

        if (index === -1) {
          res.writeHead(404, {
            "Content-Type": "application/json",
          });

          return res.end(
            JSON.stringify({
              error: "Пользователь не найден",
            })
          );
        }

        // нельзя менять логин и пароль
        users[index] = {
          ...users[index],
          name: updatedData.name,
          phone: updatedData.phone,
          email: updatedData.email,
          comment: updatedData.comment,
          consent: updatedData.consent,
        };

        fs.writeFileSync(usersFile, JSON.stringify(users, null, 2));

        res.writeHead(200, {
          "Content-Type": "application/json",
        });

        res.end(
          JSON.stringify({
            success: true,
            user: users[index],
          })
        );
      } catch {
        res.writeHead(500, {
          "Content-Type": "application/json",
        });

        res.end(
          JSON.stringify({
            error: "Ошибка сервера",
          })
        );
      }
    });

    return;
  }

  res.writeHead(404);
  res.end();
});

server.listen(PORT, () => {
  console.log(`Server started: http://localhost:${PORT}`);
});