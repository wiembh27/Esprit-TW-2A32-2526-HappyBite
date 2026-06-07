-- Story likes (one like per visitor key / session) and comments
-- Run on the happybite database after schema_stories.sql

USE happybite;

CREATE TABLE IF NOT EXISTS StoryLike (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id INT NOT NULL,
    visitor_key VARCHAR(64) NOT NULL,
    dateLike DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_story_visitor (story_id, visitor_key),
    CONSTRAINT fk_storylike_story FOREIGN KEY (story_id) REFERENCES Story (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS StoryCommentaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id INT NOT NULL,
    contenu TEXT NOT NULL,
    dateCommentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_storycomment_story FOREIGN KEY (story_id) REFERENCES Story (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
