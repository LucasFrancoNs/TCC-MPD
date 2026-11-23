-- Liga a conta de usuário (tipo = 'profissional') a um registro na tabela profissionais.
-- Rode este script se o seu banco já existia antes do painel do profissional.
-- Quem acabou de importar o banco.sql atualizado não precisa rodar isto, pois a coluna já vem criada.

ALTER TABLE profissionais
    ADD COLUMN IF NOT EXISTS usuario_id INT NULL UNIQUE AFTER id;

ALTER TABLE profissionais
    MODIFY latitude DECIMAL(10,7) NULL,
    MODIFY longitude DECIMAL(10,7) NULL;

ALTER TABLE profissionais
    ADD CONSTRAINT fk_profissional_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL;
