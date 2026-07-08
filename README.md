# PlanningPoker

>This README file contains *general information* about the idea and purpose of the project. 
>See [/docs](docs/README.md) for detailed *technic documentation*.

## Introduction 

Planning Poker is a **collaborative estimation technique** used by software development teams to **estimate the complexity and effort of tasks**. Team members independently evaluate a task, discuss different opinions, and reach a shared understanding of the required effort.  
  
This project implements a web-based PlanningPoker application that supports real-time collaborative remote task estimation[^1].

## Problem Statement

Software teams often face difficulties when estimating task complexity consistently. Different interpretations of requirements can lead to inaccurate estimates, inefficient planning, and misunderstandings between team members.
A digital PlanningPoker tool can simplify the estimation process by providing a shared environment where teams can discuss tasks and collect estimation results.

## Project Purpose

The purpose of this project is to develop a *web application that enables teams to perform collaborative Planning Poker sessions*.
The system should support:
- creating and managing estimation sessions
- adding tasks for estimation
- allowing participants to submit votes
- displaying estimation results
- supporting discussion and decision-making within the team

## Overall Description

The application provides a platform where users can participate in estimation sessions.
A typical workflow:
1. A user creates an estimation session.
2. Other participants join the session.
3. Tasks are added to the session.
4. Participants independently submit their estimates.
5. The system reveals and displays voting results.
6. The team discusses differences and agrees on a final estimate.

## Scope

### Included

- User session management
- Creating and joining PlanningPoker sessions
- Task management
- Voting and result calculation
- Displaying estimation results

### Not Included

- Integration with external project management systems
- Advanced user management
- Automated estimation algorithms

## Definitions

| Term           | Definition                                                       |     |
| -------------- | ---------------------------------------------------------------- | --- |
| Planning Poker | A team-based estimation technique for evaluating task complexity |     |
| Session        | A collaborative workspace where users estimate tasks             |     |
| Task           | An item that requires estimation by participants                 |     |
| Vote           | An individual estimation value submitted by a participant        |     |
More terms are available in [glossary](glossary.md).

## Resources

The project is developed using:
- Backend: [Symfony](https://symfony.com/) | [Twig](https://twig.symfony.com/) | [PostgreSQL](https://www.postgresql.org/)
- Frontend: Basic web technologies | [Tailwind](https://tailwindcss.com/)
- Version Control: [GitHub](https://github.com/danylo-dorofeiev/planning-poker) | [Bitbucket](https://bitbucket.org/)
- Documentation: [Obsidian](https://obsidian.md/) | [LaTex](https://www.latex-project.org/) | [Overleaf](https://www.overleaf.com/)

## Documentation

Project documentation is stored in the [/docs](docs/README.md) directory.
It contains:
- System Requirements Specification (SRS)
- Functional and Non-functional Requirements
- Use Cases
- System Design
- Architecture Documentation
- Implementation Details
- Testing Documentation

## Reference

[Kollabe Planning Poker](https://kollabe.com/de/planning-poker)

[^1]: PlanningPoker tool developed under the supervision of [Quantumfrog GmbH](https://quantumfrog.de) as a part of final project.
