-- =============================================================================
-- Communauté : lier posts, commentaires et stories aux utilisateurs.
-- Exécutez sur votre base (ex. happybite). La clé primaire de `utilisateur`
-- doit être `id` (schéma auth). Si votre table utilise `id_utilisateur`,
-- adaptez les contraintes FOREIGN KEY en conséquence.
-- =============================================================================

USE happybite;

ALTER TABLE Post
    ADD COLUMN id_utilisateur INT NULL AFTER nombreLikes,
    ADD INDEX idx_post_id_utilisateur (id_utilisateur),
    ADD CONSTRAINT fk_post_id_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE Commentaire
    ADD COLUMN id_utilisateur INT NULL AFTER post_id,
    ADD INDEX idx_commentaire_id_utilisateur (id_utilisateur),
    ADD CONSTRAINT fk_commentaire_id_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE Story
    ADD COLUMN id_utilisateur INT NULL AFTER dateCreation,
    ADD INDEX idx_story_id_utilisateur (id_utilisateur),
    ADD CONSTRAINT fk_story_id_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE StoryCommentaire
    ADD COLUMN id_utilisateur INT NULL AFTER story_id,
    ADD INDEX idx_storycommentaire_id_utilisateur (id_utilisateur),
    ADD CONSTRAINT fk_storycommentaire_id_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id)
        ON DELETE SET NULL ON UPDATE CASCADE;
