-- Copyright (C) 2024 EVARISK <technique@evarisk.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- 1.0.0

INSERT INTO `llx_c_tasks_columns` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `lowerpercent`, `upperpercent`, `position`) VALUES(1, 0, 'Backlog',    'Backlog',    'BacklogDescription',    1, '0',  '20',  1);
INSERT INTO `llx_c_tasks_columns` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `lowerpercent`, `upperpercent`, `position`) VALUES(2, 0, 'ToDo',       'ToDo',       'ToDoDescription',       1, '21', '40',  10);
INSERT INTO `llx_c_tasks_columns` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `lowerpercent`, `upperpercent`, `position`) VALUES(3, 0, 'InProgress', 'InProgress', 'InProgressDescription', 1, '41', '60',  20);
INSERT INTO `llx_c_tasks_columns` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `lowerpercent`, `upperpercent`, `position`) VALUES(4, 0, 'InReview',   'InReview',   'InReviewDescription',   1, '61', '80',  30);
INSERT INTO `llx_c_tasks_columns` (`rowid`, `entity`, `ref`, `label`, `description`, `active`, `lowerpercent`, `upperpercent`, `position`) VALUES(5, 0, 'Done',       'Done',       'DoneDescription',       1, '81', '100', 40);
