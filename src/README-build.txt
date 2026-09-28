Tailwind source for assets/css/app.css (already compiled — nothing to do for normal use).
Only needed if you change Tailwind classes in the PHP/JS files:
  npm install tailwindcss@4 @tailwindcss/cli@4
  npx @tailwindcss/cli -i src/input.css -o assets/css/app.css --minify
