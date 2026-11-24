-- Table des sessions (à créer dans la base distante utilisée par Vercel)
CREATE TABLE IF NOT EXISTS sessions (
  id             VARCHAR(128) NOT NULL PRIMARY KEY,
  data           MEDIUMTEXT   NOT NULL,
  last_activity  INT UNSIGNED NOT NULL,
  INDEX idx_sessions_activity (last_activity)
);
