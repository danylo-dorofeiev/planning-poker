# Technical Documentation

> Definitions of project-specific terms and abbreviations are provided in the [glossary](glossary.md) section.

## 1. System Requirements Specifications (SRS) 

### 1.1 Functional requirements (FRs)

[Funktional Requirements ↗](functional_requirements.md)

### 1.2 Non-functional requirements (NFRs)

[Non-Functional Requirements ↗](non-functional_requirements.md)

### 1.3 Use Cases

[Use Cases ↗](requirements/use_cases/use_cases.md)

## 2. Traceability (Appendix)

*Traceability Matrix: Use Case → Design → Implementation → Related Tests* 

## 3. System Design

### 3.1 Architecture (high-level)

The system follows a simple client-server architecture:

- Web Frontend (SPA)
- Backend API (business logic)
- WebSocket layer (real-time updates)
- Database (persistent storage)

Frontend communicates with backend via REST and WebSocket for real-time updates.
Backend manages all room state, voting logic, and data persistence.

### 3.2 Data Model

#### 3.2.1 Core Domain Model

| Entity | Description                                                                         |
| ------ | ----------------------------------------------------------------------------------- |
| Admin  | Creator and moderator of the session. Controls the voting process and participants. |
| User   |                                                                                     |
| Room   | Contains tickets and manages voting sessions.                                       |
| Ticket | Represents a task to be estimated.                                                  |
| Deck   | Defines available voting values (e.g., Fibonacci, T-shirt sizes).                   |
| Card   | Represents a user’s estimation for a ticket.                                        |

#### 3.2.1 Entity-Relationship Model (ERM)

**Chen Notation**
![Chen Notation|697](assets/jpg/chen_notation.jpg)

**Information Engineering (IE) Notation**
![IE Notation|697](assets/jpg/ie_notation.jpg)

### 3.3 Application Design
#### 3.4.1 Object Model (Class Diagram)

### 3.4 Behavior Design (Sequence / Activity)
#### 3.4.1 Sequence Diagram
#### 3.4.2 Activity Diagram

### 3.5 UI/UX Design
#### 3.5.1 Mockups
#### 3.5.2 User flows

## 4. Implementation

### 4.1 Code Structure
### 4.2 API Design
### 4.3 ADRs

## 5. Testing
### 5.1 Test Strategy
### 5.2 Test Protocol

*Test Protocol:  ID → linked FR / UC*

## 6. Conclusion