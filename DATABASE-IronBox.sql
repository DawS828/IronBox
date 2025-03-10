CREATE DATABASE GestionCasiers;
USE GestionCasiers;

-- Table des Techniciens
CREATE TABLE Technicien (
    TechnicienID INT PRIMARY KEY AUTO_INCREMENT,
    TechNom VARCHAR(50) NOT NULL,
    TechPrenom VARCHAR(50) NOT NULL,
    TechRole VARCHAR(50),
    TechBadge VARCHAR(50) UNIQUE NOT NULL
);

-- Table des IronBox (groupes de casiers)
CREATE TABLE IronBox (
    IronBoxID INT PRIMARY KEY AUTO_INCREMENT,
    IronBoxPos VARCHAR(100) NOT NULL
);

-- Table des Casiers
CREATE TABLE Casier (
    CasierID INT PRIMARY KEY AUTO_INCREMENT,
    CasierType VARCHAR(50) NOT NULL,
    CasierQte INT DEFAULT 0,
    CasierEtat VARCHAR(50) NOT NULL,
    IronBoxID INT,
    FOREIGN KEY (IronBoxID) REFERENCES IronBox(IronBoxID) ON DELETE SET NULL
);

-- Table des Matériels
CREATE TABLE Materiel (
    MaterielID INT PRIMARY KEY AUTO_INCREMENT,
    MaterielNom VARCHAR(100) NOT NULL,
    MaterielType VARCHAR(50) NOT NULL
);

-- Table de l'Historique des accès et événements
CREATE TABLE Historique (
    HistoriqueID INT PRIMARY KEY AUTO_INCREMENT,
    HistoriqueDate DATE NOT NULL,
    HistoriqueHeure TIME NOT NULL,
    HistoriqueTypeEvent VARCHAR(100) NOT NULL
);

-- Table Accéder (relation entre Technicien et Casier)
CREATE TABLE Acceder (
    TechnicienID INT,
    CasierID INT,
    PRIMARY KEY (TechnicienID, CasierID),
    FOREIGN KEY (TechnicienID) REFERENCES Technicien(TechnicienID) ON DELETE CASCADE,
    FOREIGN KEY (CasierID) REFERENCES Casier(CasierID) ON DELETE CASCADE
);

-- Table Contenir (relation entre Casier et Matériel)
CREATE TABLE Contenir (
    CasierID INT,
    MaterielID INT,
    PRIMARY KEY (CasierID, MaterielID),
    FOREIGN KEY (CasierID) REFERENCES Casier(CasierID) ON DELETE CASCADE,
    FOREIGN KEY (MaterielID) REFERENCES Materiel(MaterielID) ON DELETE CASCADE
);

-- Table Lister (relation entre Historique et les événements liés aux matériels)
CREATE TABLE Lister (
    HistoriqueID INT,
    MaterielID INT,
    PRIMARY KEY (HistoriqueID, MaterielID),
    FOREIGN KEY (HistoriqueID) REFERENCES Historique(HistoriqueID) ON DELETE CASCADE,
    FOREIGN KEY (MaterielID) REFERENCES Materiel(MaterielID) ON DELETE CASCADE
);
