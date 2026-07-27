import { MemoryRouter } from 'react-router-dom';
import { ParsianProfile } from "./parsian-profile.js";
    
export const ParsianProfileBasic = () => {
  return (
    <MemoryRouter>
      <ParsianProfile />
    </MemoryRouter>
  );
}